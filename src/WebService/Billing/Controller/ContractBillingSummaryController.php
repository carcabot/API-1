<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractBillingSummaryDataProvider;
use App\Entity\Contract;
use App\Entity\ContractBillingSummary;
use App\Entity\MonetaryAmount;

class ContractBillingSummaryController
{
    /**
     * @var ContractBillingSummaryDataProvider
     */
    private $billingSummaryDataProvider;

    /**
     * @param ContractBillingSummaryDataProvider $billingSummaryDataProvider
     */
    public function __construct(ContractBillingSummaryDataProvider $billingSummaryDataProvider)
    {
        $this->billingSummaryDataProvider = $billingSummaryDataProvider;
    }

    public function __invoke(Contract $contract): ?ContractBillingSummary
    {
        $data = $this->billingSummaryDataProvider->getSubresource(Contract::class, ['id' => $contract->getId()], ['contract_billing_summary_read'], 'GET');

        if (null === $data) {
            return new ContractBillingSummary(null, null, new MonetaryAmount(null), null, new MonetaryAmount(null));
        }

        return $data;
    }
}
