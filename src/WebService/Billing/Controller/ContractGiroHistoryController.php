<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractGiroHistoryDataProvider;
use App\Entity\Contract;

class ContractGiroHistoryController
{
    /**
     * @var ContractGiroHistoryDataProvider
     */
    private $contractGiroHistoryDataProvider;

    /**
     * @param ContractGiroHistoryDataProvider $contractGiroHistoryDataProvider
     */
    public function __construct(ContractGiroHistoryDataProvider $contractGiroHistoryDataProvider)
    {
        $this->contractGiroHistoryDataProvider = $contractGiroHistoryDataProvider;
    }

    public function __invoke(Contract $contract): array
    {
        $data = $this->contractGiroHistoryDataProvider->getSubresource(Contract::class, ['id' => $contract->getId()], ['contract_giro_history_read'], 'GET');

        if (null === $data) {
            return [];
        }

        return $data;
    }
}
