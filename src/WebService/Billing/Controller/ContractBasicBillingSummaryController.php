<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractBasicBillingSummaryDataProvider;
use App\Entity\Contract;
use App\Entity\ContractBillingSummary;
use App\Entity\MonetaryAmount;

class ContractBasicBillingSummaryController
{
    /**
     * @var ContractBasicBillingSummaryDataProvider
     */
    private $contractBasicBillingSummaryDataProvider;

    /**
     * @param ContractBasicBillingSummaryDataProvider $contractBasicBillingSummaryDataProvider
     */
    public function __construct(ContractBasicBillingSummaryDataProvider $contractBasicBillingSummaryDataProvider)
    {
        $this->contractBasicBillingSummaryDataProvider = $contractBasicBillingSummaryDataProvider;
    }

    public function __invoke(Contract $contract): ContractBillingSummary
    {
        $data = $this->contractBasicBillingSummaryDataProvider->getSubresource(Contract::class, ['id' => $contract->getId()], ['contract_billing_summary_read', 'contract_arrears_history_read', 'contract_consumption_history_read', 'contract_financial_history_read', 'contract_giro_history_read'], 'GET');

        if (null === $data) {
            return new ContractBillingSummary(null, null, new MonetaryAmount(null), null, new MonetaryAmount(null));
        }

        return $data;
    }
}
