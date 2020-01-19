<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractArrearsHistoryDataProvider;
use App\Entity\Contract;

class ContractArrearsHistoryController
{
    /**
     * @var ContractArrearsHistoryDataProvider
     */
    private $contractArrearsHistoryDataProvider;

    /**
     * @param ContractArrearsHistoryDataProvider $contractArrearsHistoryDataProvider
     */
    public function __construct(ContractArrearsHistoryDataProvider $contractArrearsHistoryDataProvider)
    {
        $this->contractArrearsHistoryDataProvider = $contractArrearsHistoryDataProvider;
    }

    public function __invoke(Contract $contract): array
    {
        $data = $this->contractArrearsHistoryDataProvider->getSubresource(Contract::class, ['id' => $contract->getId()], ['contract_arrear_history_read'], 'GET');

        if (null === $data) {
            return [];
        }

        return $data;
    }
}
