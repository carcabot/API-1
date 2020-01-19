<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractBillingInformationDataProvider;
use App\Entity\Contract;
use App\Entity\ContractBillingSummary;
use App\Entity\MonetaryAmount;

class ContractBillingInformationController
{
    /**
     * @var ContractBillingInformationDataProvider
     */
    private $contractBillingInformationDataProvider;

    /**
     * @param ContractBillingInformationDataProvider $contractBillingInformationDataProvider
     */
    public function __construct(ContractBillingInformationDataProvider $contractBillingInformationDataProvider)
    {
        $this->contractBillingInformationDataProvider = $contractBillingInformationDataProvider;
    }

    public function __invoke(Contract $contract): ContractBillingSummary
    {
        $data = $this->contractBillingInformationDataProvider->getSubresource(Contract::class, ['id' => $contract->getId()], ['contract_billing_information_read'], 'GET');

        if (null === $data) {
            return new ContractBillingSummary(null, null, new MonetaryAmount(null), null, new MonetaryAmount(null));
        }

        return $data;
    }
}
