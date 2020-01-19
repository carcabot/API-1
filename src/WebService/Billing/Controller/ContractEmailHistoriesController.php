<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractEmailHistoryDataProvider;
use App\Entity\Contract;

class ContractEmailHistoriesController
{
    private $contractEmailHistoryDataProvider;

    /**
     * @param ContractEmailHistoryDataProvider $contractEmailHistoryDataProvider
     */
    public function __construct(ContractEmailHistoryDataProvider $contractEmailHistoryDataProvider)
    {
        $this->contractEmailHistoryDataProvider = $contractEmailHistoryDataProvider;
    }

    public function __invoke(int $id): array
    {
        $data = $this->contractEmailHistoryDataProvider->getSubresource(Contract::class, ['id' => $id], ['contract_email_history_read'], 'GET');

        return $data;
    }
}
