<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 17/4/19
 * Time: 10:47 AM.
 */

namespace App\Tests\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest;

use App\Entity\ApplicationRequest;
use App\Entity\ContactPoint;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\DigitalDocument;
use App\Entity\Identification;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Entity\PriceSpecification;
use App\Entity\Promotion;
use App\Entity\QuantitativeValue;
use App\Entity\Quotation;
use App\Entity\QuotationPriceConfiguration;
use App\Entity\TariffDailyRate;
use App\Entity\TariffRate;
use App\Entity\TariffRateTerms;
use App\Entity\ThirdPartyCharge;
use App\Entity\ThirdPartyChargeConfiguration;
use App\Enum\AccountType;
use App\Enum\ContractType;
use App\Enum\IdentificationName;
use App\Enum\MeterType;
use App\Enum\PaymentMode;
use App\Enum\PostalAddressType;
use App\Enum\QuotationPricePlanType;
use App\Enum\ReferralSource;
use App\Enum\TariffRateType;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildContractApplicationRequestData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest\BuildContractApplicationRequestDataHandler;
use App\WebService\Billing\Services\DataMapper;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;

class BuildContractApplicationRequestDataHandlerTest extends TestCase
{
    public function testContractApplicationRequestDataHandlerWITHQuotationAndCustomerTypeCORPORATE()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('UEN123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getName()->willReturn('testCorporationName');
        $corporationProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $corporationProphecy = $corporationProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporationProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $thirdPartyChargeProphecy = $this->prophesize(ThirdPartyCharge::class);
        $thirdPartyChargeProphecy->getThirdPartyChargeNumber()->willReturn('testThirdPartyChargeNumber');
        $thirdPartyChargeProphecy->isEnabled()->willReturn(true);
        $thirdPartyChargeProphecy = $thirdPartyChargeProphecy->reveal();

        $thirdPartyChargeConfigurationProphecy = $this->prophesize(ThirdPartyChargeConfiguration::class);
        $thirdPartyChargeConfigurationProphecy->getConfigurationNumber()->willReturn('testConfigurationNumber');
        $thirdPartyChargeConfigurationProphecy->getCharges()->willReturn([$thirdPartyChargeProphecy]);
        $thirdPartyChargeConfigurationProphecy = $thirdPartyChargeConfigurationProphecy->reveal();

        $securityDepositProphecy = $this->prophesize(PriceSpecification::class);
        $securityDepositProphecy->getPrice()->willReturn('testPrice');
        $securityDepositProphecy->getPriceCurrency()->willReturn('testPriceCurrency');
        $securityDepositProphecy = $securityDepositProphecy->reveal();

        $quotationProphecy = $this->prophesize(Quotation::class);
        $quotationProphecy->getPaymentTerm()->willReturn('testPaymentTerm');
        $quotationProphecy->getPaymentMode()->willReturn('testPaymentMode');
        $quotationProphecy->isDepositNegotiated()->willReturn(true);
        $quotationProphecy->getSecurityDeposit()->willReturn($securityDepositProphecy);
        $quotationProphecy = $quotationProphecy->reveal();

        $quotationOfferProphecy = $this->prophesize(QuotationPriceConfiguration::class);
        $quotationOfferProphecy->getCategory()->willReturn(new QuotationPricePlanType(QuotationPricePlanType::FIXED_RATE));
        $quotationOfferProphecy->getThirdPartyChargeConfiguration()->willReturn($thirdPartyChargeConfigurationProphecy);
        $quotationOfferProphecy = $quotationOfferProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $representativeContactPointProphecy = $this->prophesize(ContactPoint::class);
        $representativeContactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $representativeContactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $representativeContactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $representativeContactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $representativeContactPoint = $representativeContactPointProphecy->reveal();

        $representativePersonProphecy = $this->prophesize(Person::class);
        $representativePersonProphecy->getName()->willReturn('testPersonName');
        $representativePersonProphecy->getContactPoints()->willReturn([$representativeContactPoint]);
        $representativePersonProphecy = $representativePersonProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['ELECTRONIC']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::COMMERCIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn($promotion);
        $applicationRequestProphecy->getQuotation()->willReturn($quotationProphecy);
        $applicationRequestProphecy->getQuotationOffer()->willReturn($quotationOfferProphecy);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($representativePersonProphecy);
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'C',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTCORPORATIONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 0,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'DiscountCode' => 'testpromotionNumber',
            'CRMThirdPartyChargesTemplate' => [
                'TemplateCode' => 'testConfigurationNumber',
                'List' => [
                    [
                        'ItemNumber' => 'testThirdPartyChargeNumber',
                        'ItemChargeType' => 0,
                    ],
                ],
            ],
            'PaymentTerm' => 'testPaymentTerm',
            'PaymentMode' => 'testPaymentMode',
            'IsDepositNegotiated' => 1,
            'DepositAmount' => 'testPrice',
            'DepositPaymentMode' => 'testPriceCurrency',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::COMMERCIAL))->willReturn('C');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => null,
            'mobile_number' => [
                'country_code' => '+00',
                'number' => null,
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => null,
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy->mapContactPoints([$representativeContactPoint])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWITHOUTQuotationAndCustomerTypeCORPORATE()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWITHOUTQuotationAndPaymentModeNULL()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::NORMAL));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(null);
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 0,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => null,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWITHOUTCustomer()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getCustomer()->willReturn(null);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWITHCustomerTypeIndividualAndNoEmailMobile()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('NRIC123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $emptyContactPointProphecy = $this->prophesize(ContactPoint::class);
        $emptyContactPointProphecy->getEmails()->willReturn([]);
        $emptyContactPointProphecy->getMobilePhoneNumbers()->willReturn([]);
        $emptyContactPointProphecy->getTelephoneNumbers()->willReturn([]);
        $emptyContactPointProphecy->getFaxNumbers()->willReturn([]);
        $emptyContactPointProphecy = $emptyContactPointProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([]);
        $contactPointProphecy->getFaxNumbers()->willReturn([]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$emptyContactPointProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy = $personProphecy->reveal();

        $applicationPersonProphecy = $this->prophesize(Person::class);
        $applicationPersonProphecy->getName()->willReturn('testPersonName');
        $applicationPersonProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $applicationPersonProphecy->getHonorificPrefix()->willReturn('Mr.');
        $applicationPersonProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $applicationPersonProphecy = $applicationPersonProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(false);
        $tariffRateProphecy->getDailyRates()->willReturn([]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::NORMAL));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $securityDepositProphecy = $this->prophesize(PriceSpecification::class);
        $securityDepositProphecy->getPrice()->willReturn('testPrice');
        $securityDepositProphecy->getPriceCurrency()->willReturn('testPriceCurrency');
        $securityDepositProphecy = $securityDepositProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn(null);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['ELECTRONIC']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::SRLP));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPersonDetails()->willReturn($applicationPersonProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn(null);
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::MANUAL));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'NRIC123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => null,
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 0,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'SRLP',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 0,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => null,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('NRIC123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$emptyContactPointProphecy])->willReturn([]);

        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithTariffRateTypeAsFIXED_RATE()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithTariffRateTypeAsDOT_OFFER()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::DOT_OFFER));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 2,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithTariffRateTypeAsPOOL_PRICE()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::POOL_PRICE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 3,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithQuotationPricePlanAsDOT_OFFER()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('UEN123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getName()->willReturn('testCorporationName');
        $corporationProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $corporationProphecy = $corporationProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporationProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $thirdPartyChargeProphecy = $this->prophesize(ThirdPartyCharge::class);
        $thirdPartyChargeProphecy->getThirdPartyChargeNumber()->willReturn('testThirdPartyChargeNumber');
        $thirdPartyChargeProphecy->isEnabled()->willReturn(true);
        $thirdPartyChargeProphecy = $thirdPartyChargeProphecy->reveal();

        $thirdPartyChargeConfigurationProphecy = $this->prophesize(ThirdPartyChargeConfiguration::class);
        $thirdPartyChargeConfigurationProphecy->getConfigurationNumber()->willReturn('testConfigurationNumber');
        $thirdPartyChargeConfigurationProphecy->getCharges()->willReturn([$thirdPartyChargeProphecy]);
        $thirdPartyChargeConfigurationProphecy = $thirdPartyChargeConfigurationProphecy->reveal();

        $securityDepositProphecy = $this->prophesize(PriceSpecification::class);
        $securityDepositProphecy->getPrice()->willReturn('testPrice');
        $securityDepositProphecy->getPriceCurrency()->willReturn('testPriceCurrency');
        $securityDepositProphecy = $securityDepositProphecy->reveal();

        $quotationProphecy = $this->prophesize(Quotation::class);
        $quotationProphecy->getPaymentTerm()->willReturn('testPaymentTerm');
        $quotationProphecy->getPaymentMode()->willReturn('testPaymentMode');
        $quotationProphecy->isDepositNegotiated()->willReturn(true);
        $quotationProphecy->getSecurityDeposit()->willReturn($securityDepositProphecy);
        $quotationProphecy = $quotationProphecy->reveal();

        $quotationOfferProphecy = $this->prophesize(QuotationPriceConfiguration::class);
        $quotationOfferProphecy->getCategory()->willReturn(new QuotationPricePlanType(QuotationPricePlanType::DOT_OFFER));
        $quotationOfferProphecy->getThirdPartyChargeConfiguration()->willReturn($thirdPartyChargeConfigurationProphecy);
        $quotationOfferProphecy = $quotationOfferProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['ELECTRONIC']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::COMMERCIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn($quotationProphecy);
        $applicationRequestProphecy->getQuotationOffer()->willReturn($quotationOfferProphecy);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'C',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTCORPORATIONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 0,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 2,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'CRMThirdPartyChargesTemplate' => [
                'TemplateCode' => 'testConfigurationNumber',
                'List' => [
                    [
                        'ItemNumber' => 'testThirdPartyChargeNumber',
                        'ItemChargeType' => 0,
                    ],
                ],
            ],
            'PaymentTerm' => 'testPaymentTerm',
            'PaymentMode' => 'testPaymentMode',
            'IsDepositNegotiated' => 1,
            'DepositAmount' => 'testPrice',
            'DepositPaymentMode' => 'testPriceCurrency',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::COMMERCIAL))->willReturn('C');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithQuotationPricePlanAsFIXED_RATE()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('UEN123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getName()->willReturn('testCorporationName');
        $corporationProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $corporationProphecy = $corporationProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporationProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $thirdPartyChargeProphecy = $this->prophesize(ThirdPartyCharge::class);
        $thirdPartyChargeProphecy->getThirdPartyChargeNumber()->willReturn('testThirdPartyChargeNumber');
        $thirdPartyChargeProphecy->isEnabled()->willReturn(true);
        $thirdPartyChargeProphecy = $thirdPartyChargeProphecy->reveal();

        $thirdPartyChargeConfigurationProphecy = $this->prophesize(ThirdPartyChargeConfiguration::class);
        $thirdPartyChargeConfigurationProphecy->getConfigurationNumber()->willReturn('testConfigurationNumber');
        $thirdPartyChargeConfigurationProphecy->getCharges()->willReturn([$thirdPartyChargeProphecy]);
        $thirdPartyChargeConfigurationProphecy = $thirdPartyChargeConfigurationProphecy->reveal();

        $securityDepositProphecy = $this->prophesize(PriceSpecification::class);
        $securityDepositProphecy->getPrice()->willReturn('testPrice');
        $securityDepositProphecy->getPriceCurrency()->willReturn('testPriceCurrency');
        $securityDepositProphecy = $securityDepositProphecy->reveal();

        $quotationProphecy = $this->prophesize(Quotation::class);
        $quotationProphecy->getPaymentTerm()->willReturn('testPaymentTerm');
        $quotationProphecy->getPaymentMode()->willReturn('testPaymentMode');
        $quotationProphecy->isDepositNegotiated()->willReturn(true);
        $quotationProphecy->getSecurityDeposit()->willReturn($securityDepositProphecy);
        $quotationProphecy = $quotationProphecy->reveal();

        $quotationOfferProphecy = $this->prophesize(QuotationPriceConfiguration::class);
        $quotationOfferProphecy->getCategory()->willReturn(new QuotationPricePlanType(QuotationPricePlanType::FIXED_RATE));
        $quotationOfferProphecy->getThirdPartyChargeConfiguration()->willReturn($thirdPartyChargeConfigurationProphecy);
        $quotationOfferProphecy = $quotationOfferProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['ELECTRONIC']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::COMMERCIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn($quotationProphecy);
        $applicationRequestProphecy->getQuotationOffer()->willReturn($quotationOfferProphecy);
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'C',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTCORPORATIONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 0,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'CRMThirdPartyChargesTemplate' => [
                'TemplateCode' => 'testConfigurationNumber',
                'List' => [
                    [
                        'ItemNumber' => 'testThirdPartyChargeNumber',
                        'ItemChargeType' => 0,
                    ],
                ],
            ],
            'PaymentTerm' => 'testPaymentTerm',
            'PaymentMode' => 'testPaymentMode',
            'IsDepositNegotiated' => 1,
            'DepositAmount' => 'testPrice',
            'DepositPaymentMode' => 'testPriceCurrency',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::COMMERCIAL))->willReturn('C');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithQuotationPricePlanAsPOOL_PRICE()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('UEN123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getName()->willReturn('testCorporationName');
        $corporationProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $corporationProphecy = $corporationProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporationProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $thirdPartyChargeProphecy = $this->prophesize(ThirdPartyCharge::class);
        $thirdPartyChargeProphecy->getThirdPartyChargeNumber()->willReturn('testThirdPartyChargeNumber');
        $thirdPartyChargeProphecy->isEnabled()->willReturn(true);
        $thirdPartyChargeProphecy = $thirdPartyChargeProphecy->reveal();

        $thirdPartyChargeConfigurationProphecy = $this->prophesize(ThirdPartyChargeConfiguration::class);
        $thirdPartyChargeConfigurationProphecy->getConfigurationNumber()->willReturn('testConfigurationNumber');
        $thirdPartyChargeConfigurationProphecy->getCharges()->willReturn([$thirdPartyChargeProphecy]);
        $thirdPartyChargeConfigurationProphecy = $thirdPartyChargeConfigurationProphecy->reveal();

        $securityDepositProphecy = $this->prophesize(PriceSpecification::class);
        $securityDepositProphecy->getPrice()->willReturn('testPrice');
        $securityDepositProphecy->getPriceCurrency()->willReturn('testPriceCurrency');
        $securityDepositProphecy = $securityDepositProphecy->reveal();

        $quotationProphecy = $this->prophesize(Quotation::class);
        $quotationProphecy->getPaymentTerm()->willReturn('testPaymentTerm');
        $quotationProphecy->getPaymentMode()->willReturn('testPaymentMode');
        $quotationProphecy->isDepositNegotiated()->willReturn(true);
        $quotationProphecy->getSecurityDeposit()->willReturn($securityDepositProphecy);
        $quotationProphecy = $quotationProphecy->reveal();

        $quotationOfferProphecy = $this->prophesize(QuotationPriceConfiguration::class);
        $quotationOfferProphecy->getCategory()->willReturn(new QuotationPricePlanType(QuotationPricePlanType::POOL_PRICE));
        $quotationOfferProphecy->getThirdPartyChargeConfiguration()->willReturn($thirdPartyChargeConfigurationProphecy);
        $quotationOfferProphecy = $quotationOfferProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['ELECTRONIC']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::COMMERCIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn($quotationProphecy);
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getQuotationOffer()->willReturn($quotationOfferProphecy);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'C',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTCORPORATIONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 0,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 3,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'CRMThirdPartyChargesTemplate' => [
                'TemplateCode' => 'testConfigurationNumber',
                'List' => [
                    [
                        'ItemNumber' => 'testThirdPartyChargeNumber',
                        'ItemChargeType' => 0,
                    ],
                ],
            ],
            'PaymentTerm' => 'testPaymentTerm',
            'PaymentMode' => 'testPaymentMode',
            'IsDepositNegotiated' => 1,
            'DepositAmount' => 'testPrice',
            'DepositPaymentMode' => 'testPriceCurrency',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::COMMERCIAL))->willReturn('C');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithQuotationThirdPartyChargeIsEnabledAsFalse()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('UEN123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getName()->willReturn('testCorporationName');
        $corporationProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $corporationProphecy = $corporationProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporationProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $thirdPartyChargeProphecy = $this->prophesize(ThirdPartyCharge::class);
        $thirdPartyChargeProphecy->getThirdPartyChargeNumber()->willReturn('testThirdPartyChargeNumber');
        $thirdPartyChargeProphecy->isEnabled()->willReturn(false);
        $thirdPartyChargeProphecy = $thirdPartyChargeProphecy->reveal();

        $thirdPartyChargeConfigurationProphecy = $this->prophesize(ThirdPartyChargeConfiguration::class);
        $thirdPartyChargeConfigurationProphecy->getConfigurationNumber()->willReturn('testConfigurationNumber');
        $thirdPartyChargeConfigurationProphecy->getCharges()->willReturn([$thirdPartyChargeProphecy]);
        $thirdPartyChargeConfigurationProphecy = $thirdPartyChargeConfigurationProphecy->reveal();

        $securityDepositProphecy = $this->prophesize(PriceSpecification::class);
        $securityDepositProphecy->getPrice()->willReturn('testPrice');
        $securityDepositProphecy->getPriceCurrency()->willReturn('testPriceCurrency');
        $securityDepositProphecy = $securityDepositProphecy->reveal();

        $quotationProphecy = $this->prophesize(Quotation::class);
        $quotationProphecy->getPaymentTerm()->willReturn('testPaymentTerm');
        $quotationProphecy->getPaymentMode()->willReturn('testPaymentMode');
        $quotationProphecy->isDepositNegotiated()->willReturn(true);
        $quotationProphecy->getSecurityDeposit()->willReturn($securityDepositProphecy);
        $quotationProphecy = $quotationProphecy->reveal();

        $quotationOfferProphecy = $this->prophesize(QuotationPriceConfiguration::class);
        $quotationOfferProphecy->getCategory()->willReturn(new QuotationPricePlanType(QuotationPricePlanType::FIXED_RATE));
        $quotationOfferProphecy->getThirdPartyChargeConfiguration()->willReturn($thirdPartyChargeConfigurationProphecy);
        $quotationOfferProphecy = $quotationOfferProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['ELECTRONIC']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::COMMERCIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn($quotationProphecy);
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getQuotationOffer()->willReturn($quotationOfferProphecy);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'C',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTCORPORATIONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 0,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'CRMThirdPartyChargesTemplate' => [
                'TemplateCode' => 'testConfigurationNumber',
                'List' => [
                    [
                        'ItemNumber' => 'testThirdPartyChargeNumber',
                        'ItemChargeType' => 1,
                    ],
                ],
            ],
            'PaymentTerm' => 'testPaymentTerm',
            'PaymentMode' => 'testPaymentMode',
            'IsDepositNegotiated' => 1,
            'DepositAmount' => 'testPrice',
            'DepositPaymentMode' => 'testPriceCurrency',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::COMMERCIAL))->willReturn('C');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSalutationAsDR()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Dr');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'DR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSalutationAsMadam()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Madam');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MDM',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSalutationAsMR()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MR',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSalutationAsMiss()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Miss');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MS',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSalutationAsMrs()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mrs.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MRS',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSourceAsBILLING_PORTAL()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mrs.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('BILLING_PORTAL');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MRS',
            'ApplicationSource' => 'SIM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSourceAsCLIENT_HOMEPAGE()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mrs.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('CLIENT_HOMEPAGE');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MRS',
            'ApplicationSource' => 'HOMEPAGE',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSourceAsCONTACT_CENTER()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mrs.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('CONTACT_CENTER');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MRS',
            'ApplicationSource' => 'CONTACT_CENTRE',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSourceAsPARTNERSHIP_PORTAL()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mrs.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('PARTNERSHIP_PORTAL');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MRS',
            'ApplicationSource' => 'PARTNER',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSourceAsMANUAL_ENTRY()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mrs.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('MANUAL_ENTRY');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MRS',
            'ApplicationSource' => 'UCRM',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }

    public function testContractApplicationRequestDataHandlerWithSourceAsSELF_SERVICE_PORTAL()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValidThrough()->willReturn($now->setDate(2020, 05, 04));
        $identificationProphecy->getValue()->willReturn('123456');
        $identificationProphecy = $identificationProphecy->reveal();

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mrs.');
        $personProphecy = $personProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getId()->willReturn(123);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $quantitativeValueProphecy = $this->prophesize(QuantitativeValue::class);
        $quantitativeValueProphecy->getValue()->willReturn('01');
        $quantitativeValueProphecy = $quantitativeValueProphecy->reveal();

        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRateProphecy->getRate()->willReturn($quantitativeValueProphecy);
        $tariffDailyRateProphecy = $tariffDailyRateProphecy->reveal();

        $tariffRateTermsProphecy = $this->prophesize(TariffRateTerms::class);
        $tariffRateTermsProphecy->getContractDuration()->willReturn('3');
        $tariffRateTermsProphecy = $tariffRateTermsProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy->getIsDailyRate()->willReturn(true);
        $tariffRateProphecy->getDailyRates()->willReturn([$tariffDailyRateProphecy]);
        $tariffRateProphecy->getTerms()->willReturn($tariffRateTermsProphecy);
        $tariffRateProphecy->getType()->willReturn(new TariffRateType(TariffRateType::FIXED_RATE));
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAcquirerCode()->willReturn('testAcquirerCode');
        $applicationRequestProphecy->getAcquirerName()->willReturn('testAcquirerName');
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['HARDCOPY']);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::RESIDENTIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->getCustomerRepresentative()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getContactPerson()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getPaymentMode()->willReturn(new PaymentMode(PaymentMode::GIRO));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getQuotation()->willReturn(null);
        $applicationRequestProphecy->getQuotationOffer()->willReturn(null);
        $applicationRequestProphecy->getRemark()->willReturn('testRemark');
        $applicationRequestProphecy->getRepresentativeDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getReferralSource()->willReturn(new ReferralSource(ReferralSource::OTHERS));
        $applicationRequestProphecy->getSalesRepName()->willReturn('testSalesRepName');
        $applicationRequestProphecy->getSpecifiedReferralSource()->willReturn('testSpecifiedReferralSource');
        $applicationRequestProphecy->getSource()->willReturn('SELF_SERVICE_PORTAL');
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'R',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => 'UEN123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Ymd'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PromotionPlanType' => 1,
            'Agency' => 'testAcquirerName',
            'PartnerCode' => 'testAcquirerCode',
            'Remarks' => 'testRemark',
            'SalesRep' => 'testSalesRepName',
            'Salutation' => 'MRS',
            'ApplicationSource' => 'SSP',
            'HowDoYouGetToKnowAboutUs' => 'OTHERS: testSpecifiedReferralSource',
            'PaymentMode' => 'GIRO',
            'Amount' => '01',
            'Term' => 3,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
            'Attachments' => [[
                'Attachment' => [
                    'FileName' => 'testAttachmentName',
                    'ContentType' => '',
                    'FileBytes' => '',
                ], ],
            ],
        ];

        $buildContractApplicationRequestData = new BuildContractApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAttachment($supplementaryFilesProphecy)->willReturn([
            'Attachment' => [
                'FileName' => 'testAttachmentName',
                'ContentType' => '',
                'FileBytes' => '',
            ],
        ]);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('UEN123456');
        $dataMapperProphecy->mapContractType(new ContractType(ContractType::RESIDENTIAL))->willReturn('R');
        $dataMapperProphecy->mapContractSubtype('CONDOMINIUM')->willReturn('CONDO');
        $dataMapperProphecy->mapContactPoints([$contactPointProphecy])->willReturn([
            'email' => 'test@test.com',
            'mobile_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'phone_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
            'fax_number' => [
                'country_code' => '+00',
                'number' => '111111',
            ],
        ]);

        $dataMapperProphecy = $dataMapperProphecy->reveal();

        $buildContractApplicationRequestDataHandler = new BuildContractApplicationRequestDataHandler($dataMapperProphecy);
        $actualApplicationRequestData = $buildContractApplicationRequestDataHandler->handle($buildContractApplicationRequestData);

        $this->assertEquals($expectedApplicationRequestData, $actualApplicationRequestData);
    }
}
