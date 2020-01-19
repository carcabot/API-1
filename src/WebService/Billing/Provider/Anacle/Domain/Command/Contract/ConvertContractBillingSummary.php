<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\Contract;

use App\Entity\ContractBillingSummary;

class ConvertContractBillingSummary
{
    /**
     * @var ContractBillingSummary
     */
    private $contractBillingSummary;

    /**
     * @param ContractBillingSummary $contractBillingSummary
     */
    public function __construct(ContractBillingSummary $contractBillingSummary)
    {
        $this->contractBillingSummary = $contractBillingSummary;
    }

    /**
     * @return ContractBillingSummary
     */
    public function getContractBillingSummary(): ContractBillingSummary
    {
        return $this->contractBillingSummary;
    }
}
