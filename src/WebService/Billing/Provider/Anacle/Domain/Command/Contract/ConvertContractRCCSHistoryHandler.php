<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\Contract;

use App\WebService\Billing\Services\DataMapper;

class ConvertContractRCCSHistoryHandler
{
    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * @param DataMapper $dataMapper
     */
    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    public function handle(ConvertContractRCCSHistory $command)
    {
        $contractRCCSHistory = $command->getContractRCCSHistory();
        $status = $this->dataMapper->mapRCCSStatus($contractRCCSHistory->getStatus());
        $contractRCCSHistory->setStatus($status);

        return $contractRCCSHistory;
    }
}
