<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\Contract;

use App\WebService\Billing\Services\DataMapper;

class ConvertContractGiroHistoryHandler
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

    public function handle(ConvertContractGiroHistory $command)
    {
        $contractGiroHistory = $command->getContractGiroHistory();
        $status = $this->dataMapper->mapGiroStatus($contractGiroHistory->getStatus());
        $contractGiroHistory->setStatus($status);

        return $contractGiroHistory;
    }
}
