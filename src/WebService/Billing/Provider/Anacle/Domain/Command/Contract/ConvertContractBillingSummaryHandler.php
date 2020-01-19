<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\Contract;

use App\WebService\Billing\Services\DataMapper;

class ConvertContractBillingSummaryHandler
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

    public function handle(ConvertContractBillingSummary $command)
    {
        $contractBillingSummary = $command->getContractBillingSummary();

        $status = $this->dataMapper->mapGiroStatus($contractBillingSummary->getCurrentGiroAccountStatus());
        $contractBillingSummary->setCurrentGiroAccountStatus($status);

        $paymentMode = $this->dataMapper->mapPaymentMode($contractBillingSummary->getPaymentMode());
        $contractBillingSummary->setPaymentMode($paymentMode);

        return $contractBillingSummary;
    }
}
