<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\ContractAction;
use App\Entity\UpdateContractAction;
use App\Enum\ActionStatus;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ContractActionType;
use App\Enum\ContractStatus;
use App\WebService\Billing\ClientInterface as BillingClient;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ContractRenewalApplicationRequestStatusUpdater
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var DisqueQueue
     */
    private $webServicesQueue;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var BillingClient
     */
    private $billingClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param CommandBus             $commandBus
     * @param string                 $documentConverterHost
     * @param DisqueQueue            $emailsQueue
     * @param DisqueQueue            $webServicesQueue
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param SerializerInterface    $serializer
     * @param BillingClient          $billingClient
     * @param LoggerInterface        $logger
     * @param string                 $timezone
     */
    public function __construct(CommandBus $commandBus, string $documentConverterHost, DisqueQueue $emailsQueue, DisqueQueue $webServicesQueue, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, SerializerInterface $serializer, BillingClient $billingClient, LoggerInterface $logger, string $timezone)
    {
        $this->commandBus = $commandBus;
        $this->documentConverterHost = $documentConverterHost;
        $this->emailsQueue = $emailsQueue;
        $this->webServicesQueue = $webServicesQueue;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->serializer = $serializer;
        $this->billingClient = $billingClient;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function processArrayData(array $data)
    {
        $failedApplicationRequest = [];

        foreach ($data as $datum) {
            if (isset($datum['applicationRequest'])) {
                try {
                    $applicationRequest = $this->updateApplicationRequest($datum);
                } catch (\Exception $ex) {
                    $failedApplicationRequest[] = $datum;
                }
            }
        }

        return $failedApplicationRequest;
    }

    protected function updateApplicationRequest(array $data)
    {
        $applicationRequest = null;
        $contract = null;

        if (isset($data['applicationRequest'])) {
            $applicationRequestData = $data['applicationRequest'];

            if (!isset($applicationRequestData['applicationRequestNumber'])) {
                throw new \Exception('Application Request Number is required');
            }
            /**
             * @var ApplicationRequest|null
             */
            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['applicationRequestNumber' => $applicationRequestData['applicationRequestNumber']]);
            /**
             * @var Contract|null
             */
            $contract = null;
            /**
             * @var ContractAction|null
             */
            $contractAction = null;
            /**
             * @var Contract|null
             */
            $oldContract = null;

            if (null !== $applicationRequest && $applicationRequestData['status'] !== $applicationRequest->getStatus()->getValue()) {
                $applicationRequest->setStatus(new ApplicationRequestStatus($applicationRequestData['status']));

                if (isset($data['contract'])) {
                    $contractData = $data['contract'];
                    $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $contractData['contractNumber']]);

                    if (null !== $contract) {
                        $endOfDay = new \DateTime();
                        $endOfDay->setTimezone($this->timezone)->setTime(23, 59, 59);

                        $oldContract = clone $contract;
                        $oldContract->setContractNumber(null);
                        $this->entityManager->persist($oldContract);
                        $this->entityManager->flush();

                        // only update values if contract is 'active'
                        if (isset($contractData['startDate'])) {
                            $utcTimezone = new \DateTimeZone('UTC');
                            $startDate = new \DateTime($contractData['startDate'], $this->timezone);
                            $startDate->setTimezone($utcTimezone);

                            $contract->setStartDate($startDate);
                            $contract->setStatus(new ContractStatus(ContractStatus::ACTIVE));
                            $contract->setTariffRate($applicationRequest->getTariffRate());

                            if (isset($contractData['lockInDate'])) {
                                $lockInDate = new \DateTime($contractData['lockInDate'], $this->timezone);
                                $contract->setLockInDate($lockInDate->setTimezone($utcTimezone));
                            }
                        }

                        // action
                        $contractAction = new UpdateContractAction();
                        $contractAction->setActionStatus(new ActionStatus(ActionStatus::COMPLETED));
                        $contractAction->setObject($oldContract);
                        $contractAction->setType(new ContractActionType(ContractActionType::CONTRACT_RENEWAL));
                        $contractAction->setInstrument($applicationRequest);
                        $contractAction->setResult($contract);

                        $this->entityManager->persist($contractAction);
                        $contract->addAction($contractAction);

                        $this->entityManager->persist($contract);
                    }
                }

                $this->entityManager->persist($applicationRequest);
                $this->entityManager->flush();

                $this->queueEmailJob($applicationRequest);
            }
        }

        return $applicationRequest;
    }

    protected function queueEmailJob(ApplicationRequest $applicationRequest)
    {
        $jobType = null;

        switch ($applicationRequest->getStatus()->getValue()) {
            case ApplicationRequestStatus::CANCELLED:
                $jobType = JobType::APPLICATION_REQUEST_CANCELLED;
                break;
            case ApplicationRequestStatus::COMPLETED:
                $jobType = JobType::APPLICATION_REQUEST_COMPLETED;
                break;
            case ApplicationRequestStatus::REJECTED:
                $jobType = JobType::APPLICATION_REQUEST_REJECTED;
                break;
            default:
                return;
        }

        $job = new DisqueJob([
            'data' => [
                'id' => $applicationRequest->getId(),
                'applicationRequest' => $this->iriConverter->getIriFromItem($applicationRequest),
            ],
            'type' => $jobType,
        ]);

        $this->emailsQueue->push($job);
        $this->webServicesQueue->push($job);
    }
}
