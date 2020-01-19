<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest;

use App\Enum\AccountType;
use App\Enum\BillSubscriptionType;
use App\Enum\IdentificationName;
use App\Enum\PaymentMode;
use App\Enum\QuotationPricePlanType;
use App\Enum\TariffRateType;
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
     * BuildContractApplicationRequestDataHandler constructor.
     *
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

        $acquirerCode = null;
        $acquirerName = null;
        $contactPerson = null;
        $contactEmailAddress = null;
        $contactMobileNumber = null;
        $contactPersonDetails = $applicationRequest->getPersonDetails();
        $contactPoints = [];
        $customer = $applicationRequest->getCustomer();
        $customerContactPoints = null;
        $customerName = null;
        $customerNRIC = null;
        $crmThirdPartyChargesTemplates = [];
        $emailAddress = null;
        $meterType = null;
        $mobileNumber = null;
        $phoneNumber = null;
        $preferredStartDate = null;
        $promotionNumber = null;
        $promotionPlanAmount = null;
        $promotionPlanTerm = null;
        $promotionPlanType = 0;
        $quotation = $applicationRequest->getQuotation();
        $quotationOffer = $applicationRequest->getQuotationOffer();
        $referralSource = null;
        $remark = null;
        $representativeContactPoints = null;
        $representativePersonDetails = $applicationRequest->getRepresentativeDetails();
        $representativeEmailAddress = null;
        $representativeMobileNumber = null;
        $salesRepName = null;
        $salutation = null;
        $source = null;
        $tariffRate = $applicationRequest->getTariffRate();
        $tariffRateNumber = null;

        if (null === $customer) {
            return [];
        }

        if (null !== $applicationRequest->getAcquirerCode()) {
            $acquirerCode = $applicationRequest->getAcquirerCode();
        }

        if (null !== $applicationRequest->getAcquirerName()) {
            $acquirerName = $applicationRequest->getAcquirerName();
        }

        if (AccountType::CORPORATE === $customer->getType()->getValue()) {
            $corporationDetails = $customer->getCorporationDetails();

            if (null !== $corporationDetails) {
                $customerNRIC = $this->dataMapper->mapIdentifierByKey($corporationDetails->getIdentifiers(), IdentificationName::UNIQUE_ENTITY_NUMBER);
                $customerName = \strtoupper($corporationDetails->getName());
            }
        } else {
            $customerDetails = $customer->getPersonDetails();
            if (null !== $customerDetails) {
                $customerNRIC = $this->dataMapper->mapIdentifierByKey($customerDetails->getIdentifiers(), IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD);
                $customerName = \strtoupper($customerDetails->getName());
                $customerContactPoints = $this->dataMapper->mapContactPoints($customerDetails->getContactPoints());
            }
        }

        if (null !== $contactPersonDetails) {
            $contactPerson = \strtoupper($contactPersonDetails->getName());
            $contactPersonContactPoints = $this->dataMapper->mapContactPoints($contactPersonDetails->getContactPoints());

            if (null === $customerContactPoints || (empty($customerContactPoints['email']) || empty($customerContactPoints['mobile_number']))) {
                $customerContactPoints = $this->dataMapper->mapContactPoints($contactPersonDetails->getContactPoints());
            }
        }

        if (null !== $applicationRequest->getPreferredStartDate()) {
            $preferredStartDate = $applicationRequest->getPreferredStartDate();
            $preferredStartDate->setTimezone($this->timezone);
            $preferredStartDate = $preferredStartDate->format('Ymd');
        }

        if (null !== $applicationRequest->getMeterType()) {
            $meterType = $applicationRequest->getMeterType()->getValue();
        }

        if (!empty($customerContactPoints['email'])) {
            $emailAddress = $customerContactPoints['email'];
        }

        if (!empty($customerContactPoints['mobile_number'])) {
            $mobileNumber = $customerContactPoints['mobile_number']['number'];
        }

        if (!empty($customerContactPoints['phone_number'])) {
            $phoneNumber = $customerContactPoints['phone_number']['number'];
        }

        if (!empty($contactPersonContactPoints['email'])) {
            $contactEmailAddress = $contactPersonContactPoints['email'];
        }

        if (!empty($contactPersonContactPoints['mobile_number'])) {
            $contactMobileNumber = $contactPersonContactPoints['mobile_number']['number'];
        }

        // this only happens for sign on behalf, when customer representative is the contact person
        if (null !== $applicationRequest->getCustomerRepresentative() &&
            null !== $applicationRequest->getContactPerson() &&
            $applicationRequest->getCustomerRepresentative()->getId() === $applicationRequest->getContactPerson()->getId() &&
            null !== $representativePersonDetails
        ) {
            $contactPerson = \strtoupper($representativePersonDetails->getName());
            $representativeContactPoints = $this->dataMapper->mapContactPoints($representativePersonDetails->getContactPoints());

            if (!empty($representativeContactPoints['email'])) {
                $representativeEmailAddress = $representativeContactPoints['email'];
            }

            if (!empty($representativeContactPoints['mobile_number'])) {
                $representativeMobileNumber = $representativeContactPoints['mobile_number']['number'];
            }

            $contactEmailAddress = $representativeEmailAddress;
            $contactMobileNumber = $representativeMobileNumber;

            if (null === $phoneNumber && !empty($representativeContactPoints['phone_number'])) {
                $phoneNumber = $representativeContactPoints['phone_number']['number'];
            }

            if (null === $emailAddress) {
                $emailAddress = $representativeEmailAddress;
            }

            if (null === $mobileNumber) {
                $mobileNumber = $representativeMobileNumber;
            }
        }

        if (null !== $applicationRequest->getReferralSource()) {
            $referralSource = $applicationRequest->getReferralSource()->getValue();

            if (null !== $applicationRequest->getSpecifiedReferralSource()) {
                $referralSource .= ': '.$applicationRequest->getSpecifiedReferralSource();
            }
        }

        if (null !== $applicationRequest->getRemark()) {
            $remark = $applicationRequest->getRemark();
        }

        if (null !== $applicationRequest->getSalesRepName()) {
            $salesRepName = $applicationRequest->getSalesRepName();
        }

        if (null !== $contactPersonDetails) {
            $salutation = $contactPersonDetails->getHonorificPrefix();

            switch ($salutation) {
                case 'Dr':
                    $salutation = 'DR';
                    break;
                case 'Madam':
                    $salutation = 'MDM';
                    break;
                case 'Miss':
                    $salutation = 'MS';
                    break;
                case 'Mr.':
                    $salutation = 'MR';
                    break;
                case 'Mrs.':
                    $salutation = 'MRS';
                    break;
                default:
                    break;
            }
        }

        if (null !== $applicationRequest->getSource()) {
            $source = $applicationRequest->getSource();

            switch ($source) {
                case 'BILLING_PORTAL':
                    $source = 'SIM';
                    break;
                case 'CLIENT_HOMEPAGE':
                    $source = 'HOMEPAGE';
                    break;
                case 'CONTACT_CENTER':
                    $source = 'CONTACT_CENTRE';
                    break;
                case 'MANUAL_ENTRY':
                    $source = 'UCRM';
                    break;
                case 'PARTNERSHIP_PORTAL':
                    $source = 'PARTNER';
                    break;
                case 'SELF_SERVICE_PORTAL':
                    $source = 'SSP';
                    break;
                default:
                    break;
            }
        }

        if (null !== $tariffRate) {
            $tariffRateNumber = $tariffRate->getTariffRateNumber();
            //if tariff rate equal true, assign $promotionPlanType as 3, and than add promotionPlanAmount, and $promotionPlanTerm in $applicationRequestData
            if (true === $tariffRate->getIsDailyRate()) {
                $tariffDailyRates = $tariffRate->getDailyRates();
                //there should always have one daily rate resulting from cloning in ApplicationRequestSubmissionListener
                if (\iter\count($tariffDailyRates) > 0) {
                    $promotionPlanAmount = $tariffDailyRates[0]->getRate()->getValue();
                }
                //check if contract duration is not null and is numeric.
                if (null !== $tariffRate->getTerms()) {
                    if (null !== $tariffRate->getTerms()->getContractDuration()) {
                        if (true === \is_numeric($tariffRate->getTerms()->getContractDuration())) {
                            $promotionPlanTerm = (int) $tariffRate->getTerms()->getContractDuration();
                        }
                    }
                }

                if ($tariffRate->getType() instanceof TariffRateType) {
                    switch ($tariffRate->getType()->getValue()) {
                        case TariffRateType::FIXED_RATE:
                            $promotionPlanType = 1;
                            break;
                        case TariffRateType::DOT_OFFER:
                            $promotionPlanType = 2;
                            break;
                        case TariffRateType::POOL_PRICE:
                            $promotionPlanType = 3;
                            break;
                    }
                }
            }
        }

        $applicationRequestData = [
            'CRMContractApplicationNumber' => $applicationRequest->getApplicationRequestNumber(),
            'ContractCustomizationIndicator' => $applicationRequest->isCustomized() * 1,
            'ContractType' => $this->dataMapper->mapContractType($applicationRequest->getContractType()),
            'ContractSubType' => $this->dataMapper->mapContractSubtype($applicationRequest->getContractSubtype()),
            'CustomerName' => \substr($customerName, 0, 255),
            'CustomerNRIC' => $customerNRIC,
            'CRMCustomerReferenceNumber' => $customer->getAccountNumber(),
            'ContactPerson' => \substr($contactPerson, 0, 50),
            'PhoneNumber' => $phoneNumber,
            'EmailAddress' => $emailAddress,
            'MobileNumber' => $mobileNumber,
            'PromoCode' => $tariffRateNumber,
            'MSSLAccountNumber' => $applicationRequest->getMsslAccountNumber(),
            'EBSAccountNumber' => $applicationRequest->getEbsAccountNumber(),
            'PreferredTurnOnDate' => $preferredStartDate,
            'CorrespondenceEmailAddress' => $contactEmailAddress,
            'CorrespondenceMobileNumber' => $contactMobileNumber,
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 0,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => $meterType,
            'SelfReadOption' => $applicationRequest->isSelfReadMeterOption() * 1,
            'PromotionPlanType' => $promotionPlanType,
            'Agency' => $acquirerName,
            'PartnerCode' => $acquirerCode,
            'Remarks' => $remark,
            'SalesRep' => $salesRepName,
            'Salutation' => $salutation,
            'ApplicationSource' => $source,
            'HowDoYouGetToKnowAboutUs' => $referralSource,
        ];

        if (null !== $applicationRequest->getPromotion()) {
            $promotion = $applicationRequest->getPromotion();
            $promotionNumber = $promotion->getPromotionNumber();

            $applicationRequestData['DiscountCode'] = $promotionNumber;
        }

        if (null !== $quotationOffer && null !== $quotation) {
            switch ($quotationOffer->getCategory()->getValue()) {
                case QuotationPricePlanType::DOT_OFFER:
                    $applicationRequestData['PromotionPlanType'] = 2;
                    break;
                case QuotationPricePlanType::FIXED_RATE:
                    $applicationRequestData['PromotionPlanType'] = 1;
                    break;
                case QuotationPricePlanType::POOL_PRICE:
                    $applicationRequestData['PromotionPlanType'] = 3;
                    break;
                default:
                    $applicationRequestData['PromotionPlanType'] = 0;
                    break;
            }

            $thirdPartyChargeConfiguration = $quotationOffer->getThirdPartyChargeConfiguration();
            if (null !== $thirdPartyChargeConfiguration) {
                $crmThirdPartyChargesTemplates = [
                    'TemplateCode' => $thirdPartyChargeConfiguration->getConfigurationNumber(),
                    'List' => [],
                ];

                foreach ($thirdPartyChargeConfiguration->getCharges() as $thirdPartyCharge) {
                    $chargeItem = [
                        'ItemNumber' => $thirdPartyCharge->getThirdPartyChargeNumber(),
                    ];

                    if (true === $thirdPartyCharge->isEnabled()) {
                        $chargeItem['ItemChargeType'] = 0;
                    } else {
                        $chargeItem['ItemChargeType'] = 1;
                    }

                    $crmThirdPartyChargesTemplates['List'][] = $chargeItem;
                }

                $applicationRequestData['CRMThirdPartyChargesTemplate'] = $crmThirdPartyChargesTemplates;
            }
            $applicationRequestData['PaymentTerm'] = $quotation->getPaymentTerm();
            $applicationRequestData['PaymentMode'] = $quotation->getPaymentMode();
            $applicationRequestData['IsDepositNegotiated'] = $quotation->isDepositNegotiated() * 1;
            $applicationRequestData['DepositAmount'] = $quotation->getSecurityDeposit()->getPrice();
            $applicationRequestData['DepositPaymentMode'] = $quotation->getSecurityDeposit()->getPriceCurrency();
        } else {
            if (null === $applicationRequest->getPaymentMode() || PaymentMode::MANUAL === $applicationRequest->getPaymentMode()->getValue()) {
                $applicationRequestData['PaymentMode'] = null;
            } else {
                $applicationRequestData['PaymentMode'] = $applicationRequest->getPaymentMode()->getValue();
            }
        }

        if (0 !== $promotionPlanType) {
            $applicationRequestData['Amount'] = $promotionPlanAmount;
            $applicationRequestData['Term'] = $promotionPlanTerm;
        }

        foreach ($applicationRequest->getBillSubscriptionTypes() as $billSubscriptionType) {
            if (BillSubscriptionType::ELECTRONIC === $billSubscriptionType) {
                $applicationRequestData['CorrespondenceViaEmail'] = 1;
                $applicationRequestData['CorrespondenceViaSMS'] = 1;
            } elseif (BillSubscriptionType::HARDCOPY === $billSubscriptionType) {
                $applicationRequestData['CorrespondenceViaMail'] = 1;
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

        // start attachments
        $applicationRequestData['Attachments'] = [];

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
        // end attachments

        return $applicationRequestData;
    }
}
