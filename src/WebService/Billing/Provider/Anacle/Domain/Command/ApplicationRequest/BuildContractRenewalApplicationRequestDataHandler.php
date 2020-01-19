<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest;

use App\WebService\Billing\Services\DataMapper;

class BuildContractRenewalApplicationRequestDataHandler
{
    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * BuildContractApplicationRequestDataHandler constructor.
     *
     * @param DataMapper $dataMapper
     */
    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
        $this->timezone = new \DateTimeZone('Asia/Singapore');
    }

    public function handle(BuildContractRenewalApplicationRequestData $command): array
    {
        $applicationRequest = $command->getApplicationRequest();

        $applicationRequestNumber = $applicationRequest->getApplicationRequestNumber();
        $contractNumber = null;
        $startDate = null;
        $tariffRateNumber = null;
        $isCustomized = false;

        if (null !== $applicationRequest->getContract()) {
            $contractNumber = $applicationRequest->getContract()->getContractNumber();
        }

        if (null !== $applicationRequest->getTariffRate()) {
            $tariffRateNumber = $applicationRequest->getTariffRate()->getTariffRateNumber();
        }

        if (null !== $applicationRequest->getPreferredStartDate()) {
            $startDate = $applicationRequest->getPreferredStartDate();
            $startDate->setTimezone($this->timezone);
            $startDate = $startDate->format('Ymd');
        }

        if (null !== $applicationRequest->isCustomized()) {
            $isCustomized = $applicationRequest->isCustomized();
        }

        $attachments = [];
        foreach ($applicationRequest->getSupplementaryFiles() as $fileAttached) {
            $attachment = $this->dataMapper->mapAttachment($fileAttached);

            if (\count($attachment) > 0) {
                $attachments[] = $attachment;
            }
        }

        $applicationRequestData = [
            'CRMFRCReContractNumber' => $applicationRequestNumber,
            'FRCContractNumber' => $contractNumber,
            'ContractStartDate' => $startDate,
            'PromoCode' => $tariffRateNumber,
            'ContractCustomizationIndicator' => $isCustomized * 1,
        ];

        $applicationRequestData['Attachments'] = [];
        if (\count($attachments) > 0) {
            $applicationRequestData['Attachments'] += $attachments;
        }

        return $applicationRequestData;
    }
}
