<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\Contract;

use App\Entity\ContractRccsHistory;

class ConvertContractRCCSHistory
{
    /**
     * @var ContractRccsHistory
     */
    private $contractRCCSHistory;

    /**
     * @param ContractRccsHistory $contractRCCSHistory
     */
    public function __construct(ContractRccsHistory $contractRCCSHistory)
    {
        $this->contractRCCSHistory = $contractRCCSHistory;
    }

    /**
     * @return ContractRccsHistory
     */
    public function getContractRCCSHistory(): ContractRccsHistory
    {
        return $this->contractRCCSHistory;
    }
}
