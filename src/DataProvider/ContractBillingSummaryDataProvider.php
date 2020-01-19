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

final class ContractBillingSummaryDataProvider implements SubresourceDataProviderInterface, RestrictedDataProviderInterface
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
        $contractApplication = null;
        $billingSummary = null;

        if ($contract instanceof Contract && \in_array('contract_billing_summary_read', $context, true)) {
            $applicationRequests = $this->entityManager->getRepository(ApplicationRequest::class)->findBy(['contract' => $contract->getId()]);

            foreach ($applicationRequests as $applicationRequest) {
                if (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
                    $contractApplication = $applicationRequest;
                    break;
                }
            }

            $billingSummary = $this->webServiceClient->getContractBillingSummary($contract, $contractApplication);
            if (null !== $billingSummary &&
                null !== $billingSummary->getPaymentMode() &&
                (
                    null === $contract->getPaymentMode() ||
                    $contract->getPaymentMode()->getValue() !== $billingSummary->getPaymentMode()
                )
            ) {
                $contract->setPaymentMode(new PaymentMode($billingSummary->getPaymentMode()));
                $this->entityManager->persist($contract);

                $job = new DisqueJob([
                    'data' => [
                        'id' => $contract->getId(),
                        'paymentMode' => $billingSummary->getPaymentMode(),
                    ],
                    'type' => JobType::CONTRACT_UPDATE_PAYMENT_MODE,
                ]);

                $this->webServicesQueue->push($job);
            }
        }

        return $billingSummary;
    }
}
