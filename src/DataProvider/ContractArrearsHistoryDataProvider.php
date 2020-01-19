<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use App\Entity\Contract;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;

class ContractArrearsHistoryDataProvider implements SubresourceDataProviderInterface, RestrictedDataProviderInterface
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
        return Contract::class === $resourceClass;
    }

    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $repository = $this->entityManager->getRepository($resourceClass);

        $contract = $repository->findOneBy($identifiers);
        $arrearsHistory = null;

        if ($contract instanceof Contract && \in_array('contract_arrear_history_read', $context, true)) {
            $arrearsHistory = $this->webServiceClient->getContractArrearsHistory($contract);
        }

        return $arrearsHistory;
    }
}
