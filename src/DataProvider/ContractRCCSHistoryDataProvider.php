<?php

declare(strict_types=1);

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use App\Disque\JobType;
use App\Entity\Contract;
use App\Entity\ContractRccsHistory;
use App\Enum\PaymentMode;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;

class ContractRCCSHistoryDataProvider implements SubresourceDataProviderInterface, RestrictedDataProviderInterface
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

        /**
         * @var Contract
         */
        $contract = $repository->find($identifiers['id']);
        $result = [];

        if ($contract instanceof Contract && \in_array('contract_rccs_history_read', $context, true)) {
            $result = $this->webServiceClient->getContractRCCSHistory($contract);

            if (null === $result) {
                $result = [];
            }

            foreach ($result as $contractRCCSHistory) {
                if ($contractRCCSHistory instanceof ContractRccsHistory) {
                    if ('ACTIVE' === $contractRCCSHistory->getStatus()) {
                        $contract->setPaymentMode(new PaymentMode(PaymentMode::RCCS));
                        $this->entityManager->persist($contract);

                        $job = new DisqueJob([
                            'data' => [
                                'id' => $contract->getId(),
                                'paymentMode' => PaymentMode::RCCS,
                            ],
                            'type' => JobType::CONTRACT_UPDATE_PAYMENT_MODE,
                        ]);

                        $this->webServicesQueue->push($job);
                        break;
                    }
                }
            }
        }

        return $result;
    }
}
