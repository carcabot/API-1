<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractEmailHistoryDataProvider;
use App\Entity\Contract;
use App\Entity\ContractEmailHistory;

class ContractEmailHistoryController
{
    private $contractEmailHistoryDataProvider;

    /**
     * @param ContractEmailHistoryDataProvider $contractEmailHistoryDataProvider
     */
    public function __construct(ContractEmailHistoryDataProvider $contractEmailHistoryDataProvider)
    {
        $this->contractEmailHistoryDataProvider = $contractEmailHistoryDataProvider;
    }

    public function __invoke(string $id, string $emailHistoryId): ?ContractEmailHistory
    {
        $data = $this->contractEmailHistoryDataProvider->getSubresource(Contract::class, ['id' => $id, 'emailHistoryId' => $emailHistoryId], ['contract_email_history_read'], 'GET');

        return $data;
    }
}
