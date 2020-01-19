<?php

declare(strict_types=1);

namespace App\WebService\Billing\Controller;

use App\DataProvider\ContractConsumptionsByBillingPeriodDataProvider;
use App\Entity\Contract;

class ContractConsumptionsByBillingPeriodController
{
    /**
     * @var ContractConsumptionsByBillingPeriodDataProvider
     */
    private $contractConsumptionsByBillingPeriodDataProvider;

    /**
     * @param ContractConsumptionsByBillingPeriodDataProvider $contractConsumptionsByBillingPeriodDataProvider
     */
    public function __construct(ContractConsumptionsByBillingPeriodDataProvider $contractConsumptionsByBillingPeriodDataProvider)
    {
        $this->contractConsumptionsByBillingPeriodDataProvider = $contractConsumptionsByBillingPeriodDataProvider;
    }

    public function __invoke(Contract $contract): array
    {
        $data = $this->contractConsumptionsByBillingPeriodDataProvider->getSubresource(Contract::class, ['id' => $contract->getId()], ['contract_consumptions_by_billing_period_read'], 'GET');

        if (null === $data) {
            return [];
        }

        return $data;
    }
}
