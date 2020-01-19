<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractFinancialHistoryDataProvider;
use App\Entity\Contract;

class ContractFinancialHistoryController
{
    /**
     * @var ContractFinancialHistoryDataProvider
     */
    private $contractFinancialHistoryDataProvider;

    /**
     * @param ContractFinancialHistoryDataProvider $contractFinancialHistoryDataProvider
     */
    public function __construct(ContractFinancialHistoryDataProvider $contractFinancialHistoryDataProvider)
    {
        $this->contractFinancialHistoryDataProvider = $contractFinancialHistoryDataProvider;
    }

    public function __invoke(Contract $contract): array
    {
        $data = $this->contractFinancialHistoryDataProvider->getSubresource(Contract::class, ['id' => $contract->getId()], ['contract_financial_history_read'], 'GET');

        if (null === $data) {
            return [];
        }

        return $data;
    }
}
