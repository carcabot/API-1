<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest;

use App\Enum\IdentificationName;
use App\WebService\Billing\Services\DataMapper;

class BuildAccountClosureApplicationRequestDataHandler
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
     * @param DataMapper $dataMapper
     */
    public function __construct(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
        $this->timezone = new \DateTimeZone('Asia/Singapore');
    }

    public function handle(BuildAccountClosureApplicationRequestData $command): array
    {
        $applicationRequest = $command->getApplicationRequest();

        $contractNumber = null;
        $contactPoints = [];
        $refundee = $applicationRequest->getRefundee();
        $refundeeDetails = $applicationRequest->getRefundeeDetails();
        $customer = $applicationRequest->getCustomer();
        $refundeeNRIC = null;
        $refundeeName = null;
        $preferredEndDate = null;
        $isDifferentPayee = true;
        $refundType = null;

        if (null !== $refundeeDetails) {
            $refundeeNRIC = $this->dataMapper->mapIdentifierByKey($refundeeDetails->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
            $refundeeName = \strtoupper($refundeeDetails->getName());
        }

        if ($refundee === $customer) {
            $isDifferentPayee = false;
        }

        if (null !== $applicationRequest->getPreferredEndDate()) {
            $preferredEndDate = $applicationRequest->getPreferredEndDate();
            $preferredEndDate->setTimezone($this->timezone);
            $preferredEndDate = $preferredEndDate->format('Ymd');
        }

        if (null !== $applicationRequest->getContract()) {
            $contractNumber = $applicationRequest->getContract()->getContractNumber();
        }

        if (null !== $applicationRequest->getDepositRefundType()) {
            $refundType = $this->dataMapper->mapRefundType($applicationRequest->getDepositRefundType());
        }

        $accountClosureRequestData = [
            'CRMContractClosureNumber' => $applicationRequest->getApplicationRequestNumber(),
            'FRCContractNumber' => $contractNumber,
            'RequestTransferOutDate' => $preferredEndDate,
            'Remarks' => $applicationRequest->getRemark(),
            'Deposit' => $refundType,
            'DifferentPayeeIndicator' => $isDifferentPayee,
            'RefundPayeeName' => $refundeeName,
            'RefundPayeeNRIC' => $refundeeNRIC,
            'TerminationReason' => $applicationRequest->getTerminationReason(),
            'SelfReadOption' => $applicationRequest->isSelfReadMeterOption() * 1,
        ];

        $addressData = [];
        foreach ($applicationRequest->getAddresses() as $address) {
            $addressFields = $this->dataMapper->mapAddressFields($address);

            if (\count($addressFields) > 0) {
                $addressData += $addressFields;
            }
        }

        $accountClosureRequestData += $addressData;

        return $accountClosureRequestData;
    }
}
