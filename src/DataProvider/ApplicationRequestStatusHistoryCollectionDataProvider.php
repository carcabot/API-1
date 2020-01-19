<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use App\Entity\ApplicationRequest;
use App\Entity\ApplicationRequestStatusHistoryCollection;
use App\Enum\ApplicationRequestStatus;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;

final class ApplicationRequestStatusHistoryCollectionDataProvider implements SubresourceDataProviderInterface, RestrictedDataProviderInterface
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
     * @param EntityManagerInterface $entityManager
     * @param WebServiceClient       $webServiceClient
     */
    public function __construct(EntityManagerInterface $entityManager, WebServiceClient $webServiceClient)
    {
        $this->entityManager = $entityManager;
        $this->webServiceClient = $webServiceClient;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return ApplicationRequest::class === $resourceClass;
    }

    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $repository = $this->entityManager->getRepository($resourceClass);

        $applicationRequest = $repository->findOneBy($identifiers);
        $statusHistoryCollection = new ApplicationRequestStatusHistoryCollection();

        if ($applicationRequest instanceof ApplicationRequest && \in_array('status_history_read', $context, true) && !\in_array($applicationRequest->getStatus()->getValue(), [
            ApplicationRequestStatus::DRAFT,
            ApplicationRequestStatus::PARTNER_DRAFT,
        ], true)) {
            $statusHistories = $this->webServiceClient->getApplicationRequestStatusHistory($applicationRequest);

            foreach ($statusHistories as $statusHistory) {
                $statusHistoryCollection->addStatusHistory($statusHistory);
            }
        }

        return $statusHistoryCollection;
    }
}
