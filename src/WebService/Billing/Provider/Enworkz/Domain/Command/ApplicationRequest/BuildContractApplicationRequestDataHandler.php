<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest;

use App\Enum\AccountType;
use App\Enum\BillSubscriptionType;
use App\Enum\IdentificationName;
use App\WebService\Billing\Services\DataMapper;

class BuildContractApplicationRequestDataHandler
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

    public function handle(BuildContractApplicationRequestData $command): array
    {
        $applicationRequest = $command->getApplicationRequest();

        $contactPerson = null;
        $contactPoints = [];
        $customer = $applicationRequest->getCustomer();
        $customerName = null;
        $customerNRIC = null;
        $emailAddress = null;
        $meterType = null;
        $mobileNumber = null;
        $phoneNumber = null;
        $preferredStartDate = null;
        $tariffRateNumber = null;

        if (null === $customer) {
            return [];
        }

        $contactPersonDetails = $applicationRequest->getPersonDetails();

        if (AccountType::CORPORATE === $customer->getType()->getValue()) {
            $corporationDetails = $customer->getCorporationDetails();

            if (null !== $corporationDetails) {
                $customerNRIC = $this->dataMapper->mapIdentifierByKey($corporationDetails->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER);
                $customerName = \strtoupper($corporationDetails->getName());

                if (null !== $contactPersonDetails) {
                    $contactPerson = \strtoupper($contactPersonDetails->getName());
                    $contactPoints = $this->dataMapper->mapContactPoints($contactPersonDetails->getContactPoints());
                }
            }
        } else {
            if (null !== $contactPersonDetails) {
                $customerNRIC = $this->dataMapper->mapIdentifierByKey($contactPersonDetails->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                $customerName = \strtoupper($contactPersonDetails->getName());
                $contactPerson = \strtoupper($contactPersonDetails->getName());

                $contactPoints = $this->dataMapper->mapContactPoints($contactPersonDetails->getContactPoints());
            }
        }

        if (null !== $applicationRequest->getPreferredStartDate()) {
            $preferredStartDate = $applicationRequest->getPreferredStartDate();
            $preferredStartDate->setTimezone($this->timezone);
            $preferredStartDate = $preferredStartDate->format('Y-m-d');
        }

        if (null !== $applicationRequest->getMeterType()) {
            $meterType = $applicationRequest->getMeterType()->getValue();
        }

        if (!empty($contactPoints['email'])) {
            $emailAddress = $contactPoints['email'];
        }

        if (!empty($contactPoints['mobile_number'])) {
            $mobileNumber = $contactPoints['mobile_number']['number'];
        }

        if (!empty($contactPoints['phone_number'])) {
            $phoneNumber = $contactPoints['phone_number']['number'];
        }

        $tariffRate = $applicationRequest->getTariffRate();
        if (null !== $tariffRate) {
            $tariffRateNumber = $tariffRate->getTariffRateNumber();
        }

        $applicationRequestData = [
            'CRMContractApplicationNumber' => $applicationRequest->getApplicationRequestNumber(),
            'ContractCustomizationIndicator' => $applicationRequest->isCustomized() * 1,
            'ContractType' => $this->dataMapper->mapContractType($applicationRequest->getContractType()),
            'ContractSubType' => $this->dataMapper->mapContractSubtype($applicationRequest->getContractSubtype()),
            'CustomerName' => $customerName,
            'CustomerNRIC' => $customerNRIC,
            'CRMCustomerReferenceNumber' => $customer->getAccountNumber(),
            'ContactPerson' => $contactPerson,
            'PhoneNumber' => $phoneNumber,
            'EmailAddress' => $emailAddress,
            'MobileNumber' => $mobileNumber,
            'PromoCode' => $tariffRateNumber,
            'MSSLAccountNumber' => $applicationRequest->getMsslAccountNumber(),
            'EBSAccountNumber' => $applicationRequest->getEbsAccountNumber(),
            'PreferredTurnOnDate' => $preferredStartDate,
            'CorrespondenceEmailAddress' => $emailAddress,
            'CorrespondenceMobileNumber' => $mobileNumber,
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 0,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => $meterType,
            'SelfReadOption' => $applicationRequest->isSelfReadMeterOption() * 1,
            'Attachments' => [],
        ];

        if (null === $applicationRequest->isRecurringOption()) {
            $applicationRequestData['PaymentMode'] = null;
        } elseif (true === $applicationRequest->isRecurringOption()) {
            $applicationRequestData['PaymentMode'] = 'RCCS';
        } else {
            $applicationRequestData['PaymentMode'] = 'GIRO';
        }

        if (null !== $applicationRequest->getPromotion()) {
            $promotion = $applicationRequest->getPromotion();
            $promotionNumber = $promotion->getPromotionNumber();

            $applicationRequestData['DiscountCode'] = $promotionNumber;
        }

        foreach ($applicationRequest->getBillSubscriptionTypes() as $billSubscriptionType) {
            if (BillSubscriptionType::ELECTRONIC === $billSubscriptionType) {
                $applicationRequestData['CorrespondenceViaEmail'] = 1;
                $applicationRequestData['CorrespondenceViaSMS'] = 1;
            } elseif (BillSubscriptionType::HARDCOPY === $billSubscriptionType) {
                $applicationRequestData['CorrespondenceViaMail'] = 1;
            }
        }

        foreach ($applicationRequest->getAddonServices() as $addonService) {
            if (null !== $addonService->getName()) {
                $applicationRequestData['ValueAddedService'] = $addonService->getName();
                break;
            }
        }

        if (null !== $applicationRequest->getCreator()) {
            if (null !== $applicationRequest->getCreator()->getEmail()) {
                $applicationRequestData['CreatedBy'] = $applicationRequest->getCreator()->getEmail();
            } elseif (null !== $applicationRequest->getCreator()->getUsername()) {
                $applicationRequestData['CreatedBy'] = $applicationRequest->getCreator()->getUsername();
            }
        }

        $addressData = [];
        foreach ($applicationRequest->getAddresses() as $address) {
            $addressFields = $this->dataMapper->mapAddressFields($address);

            if (\count($addressFields) > 0) {
                $addressData += $addressFields;
            }
        }

        $applicationRequestData += $addressData;

        $attachments = [];
        foreach ($applicationRequest->getSupplementaryFiles() as $fileAttached) {
            $attachment = $this->dataMapper->mapAttachment($fileAttached);

            if (\count($attachment) > 0) {
                $attachments[] = $attachment;
            }
        }

        if (\count($attachments) > 0) {
            $applicationRequestData['Attachments'] += $attachments;
        }

        return $applicationRequestData;
    }
}
