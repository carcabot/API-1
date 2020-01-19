<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\Contract;

use App\Entity\ContractGiroHistory;

class ConvertContractGiroHistory
{
    /**
     * @var ContractGiroHistory
     */
    private $contractGiroHistory;

    /**
     * @param ContractGiroHistory $contractGiroHistory
     */
    public function __construct(ContractGiroHistory $contractGiroHistory)
    {
        $this->contractGiroHistory = $contractGiroHistory;
    }

    /**
     * @return ContractGiroHistory
     */
    public function getContractGiroHistory(): ContractGiroHistory
    {
        return $this->contractGiroHistory;
    }
}
