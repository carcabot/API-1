<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest;

use App\WebService\Billing\Services\DataMapper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BuildContractRenewalApplicationRequestDataHandler
{
    /**
     * @var DataMapper
     */
    private $dataMapper;

    /**
     * BuildContractApplicationRequestDataHandler constructor.
     *
     * @param DataMapper $dataMapper
     */
    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    public function handle(BuildContractRenewalApplicationRequestData $command): array
    {
        $applicationRequest = $command->getApplicationRequest();

        $applicationRequestNumber = $applicationRequest->getApplicationRequestNumber();
        $contractNumber = null;
        $startDate = null;
        $tariffRateNumber = null;
        $isCustomized = null;

        if (null !== $applicationRequest->getContract()) {
            $contractNumber = $applicationRequest->getContract()->getContractNumber();
        } else {
            throw new BadRequestHttpException('Contract Is required');
        }

        if (null !== $applicationRequest->getTariffRate()) {
            $tariffRateNumber = $applicationRequest->getTariffRate()->getTariffRateNumber();
        } else {
            throw new BadRequestHttpException('Tariff Rate is required');
        }

        if (null !== $applicationRequest->isCustomized()) {
            $isCustomized = $applicationRequest->isCustomized();
        } else {
            throw new BadRequestHttpException('isCustomized is required');
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
