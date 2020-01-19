<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractRCCSHistoryDataProvider;
use App\Entity\Contract;

class ContractRCCSHistoryController
{
    /**
     * @var ContractRCCSHistoryDataProvider
     */
    private $contractRCCSHistoryDataProvider;

    /**
     * @param ContractRCCSHistoryDataProvider $contractRCCSHistoryDataProvider
     */
    public function __construct(ContractRCCSHistoryDataProvider $contractRCCSHistoryDataProvider)
    {
        $this->contractRCCSHistoryDataProvider = $contractRCCSHistoryDataProvider;
    }

    public function __invoke(Contract $contract): array
    {
        $data = $this->contractRCCSHistoryDataProvider->getSubresource(Contract::class, ['id' => $contract->getId()], ['contract_rccs_history_read'], 'GET');

        return $data;
    }
}
