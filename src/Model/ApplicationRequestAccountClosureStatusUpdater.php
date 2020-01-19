<?php

declare(strict_types=1);

namespace App\Model;

use App\Domain\Command\Contract\UpdatePointCreditsActions;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\DeactivateContractCreditsAction;
use App\Entity\UpdateContractAction;
use App\Enum\ActionStatus;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ContractActionType;
use App\Enum\ContractStatus;
use App\Enum\CustomerAccountStatus;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;

class ApplicationRequestAccountClosureStatusUpdater
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
    }

    public function processArrayData(array $data)
    {
        $failedApplicationRequest = [];

        foreach ($data as $datum) {
            try {
                $this->updateStatus($datum);
            } catch (\Exception $ex) {
                $failedApplicationRequest[] = $datum;
            }
        }
        $this->entityManager->flush();

        return $failedApplicationRequest;
    }

    protected function updateStatus(array $data)
    {
        if (!isset($data['applicationRequest']['applicationRequestNumber'])) {
            throw new \Exception('Application Request Number is required');
        }

        $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy([
            'applicationRequestNumber' => $data['applicationRequest']['applicationRequestNumber'],
        ]);

        if (null !== $applicationRequest) {
            $applicationRequest->setStatus(new ApplicationRequestStatus($data['applicationRequest']['status']));
            $this->entityManager->persist($applicationRequest);

            if (null !== $applicationRequest->getContract()) {
                if ($data['contract']['contractNumber'] === $applicationRequest->getContract()->getContractNumber()) {
                    $this->updateContractStatus($applicationRequest->getContract(), $applicationRequest);
                }
            }
        }
    }

    protected function updateContractStatus(Contract $contract, ApplicationRequest $applicationRequest)
    {
        if (ApplicationRequestStatus::COMPLETED === $applicationRequest->getStatus()->getValue()) {
            $actionExists = false;
            foreach ($contract->getActions() as $action) {
                if (ActionStatus::COMPLETED === $action->getActionStatus()->getValue() &&
                    null !== $action->getInstrument() &&
                    $applicationRequest->getId() === $action->getInstrument()->getId()
                ) {
                    $actionExists = true;
                    break;
                }
            }

            if (false === $actionExists) {
                $oldContract = clone $contract;
                $oldContract->setContractNumber(null);
                $contractAction = new UpdateContractAction();
                $contractAction->setActionStatus(new ActionStatus(ActionStatus::COMPLETED));
                $contractAction->setObject($oldContract);
                $contractAction->setResult($contract);
                $contractAction->setInstrument($applicationRequest);
                $contractAction->setType(new ContractActionType(ContractActionType::ACCOUNT_CLOSURE));

                $this->entityManager->persist($contractAction);

                $contract->addAction($contractAction);
            }

            $contract->setStatus(new ContractStatus(ContractStatus::INACTIVE));

            if ($contract->getPointCreditsBalance()->getValue() > 0) {
                // deactivate credits
                $deactivateContractCreditAction = new DeactivateContractCreditsAction();
                $deactivateContractCreditAction->setAmount($contract->getPointCreditsBalance()->getValue());
                $deactivateContractCreditAction->setEndTime(new \DateTime());
                $deactivateContractCreditAction->setStartTime(new \DateTime());
                $deactivateContractCreditAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                $deactivateContractCreditAction->setDescription('Contract Closure');
                $deactivateContractCreditAction->setInstrument($applicationRequest);
                $deactivateContractCreditAction->setObject($contract);

                $this->commandBus->handle(new UpdateTransaction($deactivateContractCreditAction));
                $this->commandBus->handle(new UpdatePointCreditsActions($contract, $deactivateContractCreditAction));

                $this->entityManager->persist($deactivateContractCreditAction);
            }

            $endDate = $applicationRequest->getPreferredEndDate();
            if (null !== $endDate) {
                $contract->setEndDate(clone $endDate);
            }

            $this->entityManager->persist($contract);
            $this->entityManager->flush();

            $customer = $contract->getCustomer();
            if (null !== $customer->getDefaultCreditsContract() && $contract->getContractNumber() === $customer->getDefaultCreditsContract()->getContractNumber()) {
                $customer->setDefaultCreditsContract(null);
                $this->entityManager->persist($customer);
                $this->entityManager->flush();
            }

            $this->updateCustomerAccountStatus($customer);
        }
    }

    protected function updateCustomerAccountStatus(CustomerAccount $customerAccount)
    {
        $isActive = false;
        $contracts = $customerAccount->getContracts();

        foreach ($contracts as $contract) {
            if (null !== $contract->getContractNumber() && ContractStatus::ACTIVE === $contract->getStatus()->getValue()) {
                $isActive = true;
                break;
            }
        }

        if (!$isActive) {
            $customerAccount->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE));

            $this->entityManager->persist($customerAccount);
            $this->entityManager->flush();
        }
    }
}
