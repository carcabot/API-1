<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use App\Disque\JobType;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Enum\ApplicationRequestType;
use App\Enum\PaymentMode;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;

class ContractBillingInformationDataProvider implements SubresourceDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @var DisqueQueue
     */
    private $webServicesQueue;

    /**
     * @param EntityManagerInterface $entityManager
     * @param WebServiceClient       $webServiceClient
     * @param DisqueQueue            $webServicesQueue
     */
    public function __construct(EntityManagerInterface $entityManager, WebServiceClient $webServiceClient, DisqueQueue $webServicesQueue)
    {
        $this->entityManager = $entityManager;
        $this->webServiceClient = $webServiceClient;
        $this->webServicesQueue = $webServicesQueue;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Contract::class === $resourceClass;
    }

    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $repository = $this->entityManager->getRepository($resourceClass);

        $contract = $repository->findOneBy($identifiers);
        $billingInformation = null;

        if ($contract instanceof Contract && \in_array('contract_billing_information_read', $context, true)) {
            if ('Enworkz' === $this->webServiceClient->getProviderName()) {
                $contractApplication = null;
                $applicationRequests = $this->entityManager->getRepository(ApplicationRequest::class)->findBy(['contract' => $contract->getId()]);

                foreach ($applicationRequests as $applicationRequest) {
                    if (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
                        $contractApplication = $applicationRequest;
                        break;
                    }
                }
                $billingInformation = $this->webServiceClient->getContractBillingSummary($contract, $contractApplication);
            } else {
                $billingInformation = $this->webServiceClient->getContractBillingInformation($contract);
            }

            if (null !== $billingInformation && null !== $billingInformation->getPaymentMode() &&
                (
                    null === $contract->getPaymentMode() ||
                    $contract->getPaymentMode()->getValue() !== $billingInformation->getPaymentMode()
                )
            ) {
                $contract->setPaymentMode(new PaymentMode($billingInformation->getPaymentMode()));
                $this->entityManager->persist($contract);

                $job = new DisqueJob([
                    'data' => [
                        'id' => $contract->getId(),
                        'paymentMode' => $billingInformation->getPaymentMode(),
                    ],
                    'type' => JobType::CONTRACT_UPDATE_PAYMENT_MODE,
                ]);

                $this->webServicesQueue->push($job);
            }
        }

        return $billingInformation;
    }
}
