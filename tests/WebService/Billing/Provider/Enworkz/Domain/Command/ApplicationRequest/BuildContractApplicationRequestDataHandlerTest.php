<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 18/4/19
 * Time: 11:00 AM.
 */

namespace App\Tests\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest;

use App\Entity\AddonService;
use App\Entity\ApplicationRequest;
use App\Entity\ContactPoint;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\DigitalDocument;
use App\Entity\Identification;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Entity\Promotion;
use App\Entity\TariffRate;
use App\Entity\User;
use App\Enum\AccountType;
use App\Enum\ContractType;
use App\Enum\IdentificationName;
use App\Enum\MeterType;
use App\Enum\PostalAddressType;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildContractApplicationRequestData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildContractApplicationRequestDataHandler;
use App\WebService\Billing\Services\DataMapper;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;

class BuildContractApplicationRequestDataHandlerTest extends TestCase
{
    public function testContractApplicationRequestDataHandlerWithCustomerTypeAsCorporate()
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

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getName()->willReturn('testCorporationName');
        $corporationProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $corporationProphecy = $corporationProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporationProphecy);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $addOnServiceProphecy = $this->prophesize(AddonService::class);
        $addOnServiceProphecy->getName()->willReturn('testAddOnServiceName');
        $addOnServiceProphecy = $addOnServiceProphecy->reveal();

        $promotionProphecy = $this->prophesize(Promotion::class);
        $promotionProphecy->getPromotionNumber()->willReturn('testpromotionNumber');
        $promotion = $promotionProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getEmail()->willReturn('testUserEmail@test.com');
        $userProphecy = $userProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAddonServices()->willReturn([$addOnServiceProphecy]);
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getCreator()->willReturn($userProphecy);
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->isRecurringOption()->willReturn(false);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['ELECTRONIC', 'HARDCOPY']);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::COMMERCIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn($promotion);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);

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
            'PreferredTurnOnDate' => $now->format('Y-m-d'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PaymentMode' => 'GIRO',
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
            'DiscountCode' => 'testpromotionNumber',
            'ValueAddedService' => 'testAddOnServiceName',
            'CreatedBy' => 'testUserEmail@test.com',
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

    public function testContractApplicationRequestDataHandlerWithCustomerTypeAsIndividual()
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

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personProphecy = $personProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy = $customerAccountProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $addOnServiceProphecy = $this->prophesize(AddonService::class);
        $addOnServiceProphecy->getName()->willReturn('testAddOnServiceName');
        $addOnServiceProphecy = $addOnServiceProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getEmail()->willReturn(null);
        $userProphecy->getUsername()->willReturn('testUsername');
        $userProphecy = $userProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAddonServices()->willReturn([$addOnServiceProphecy]);
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $applicationRequestProphecy->getCreator()->willReturn($userProphecy);
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->isRecurringOption()->willReturn(true);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['ELECTRONIC', 'HARDCOPY']);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::COMMERCIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedApplicationRequestData = [
            'CRMContractApplicationNumber' => 'SWAP123456',
            'ContractCustomizationIndicator' => 1,
            'ContractType' => 'C',
            'ContractSubType' => 'CONDO',
            'CustomerName' => 'TESTPERSONNAME',
            'CustomerNRIC' => '123456',
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'ContactPerson' => 'TESTPERSONNAME',
            'PhoneNumber' => '111111',
            'EmailAddress' => 'test@test.com',
            'MobileNumber' => '111111',
            'PromoCode' => 'SWPC123456',
            'MSSLAccountNumber' => 'MSSL123456',
            'EBSAccountNumber' => 'EBS123456',
            'PreferredTurnOnDate' => $now->format('Y-m-d'),
            'CorrespondenceEmailAddress' => 'test@test.com',
            'CorrespondenceMobileNumber' => '111111',
            'CorrespondenceViaFax' => 0,
            'CorrespondenceViaSMS' => 1,
            'CorrespondenceViaSelfCollect' => 0,
            'CorrespondenceViaMail' => 1,
            'CorrespondenceViaEmail' => 1,
            'MeterOption' => 'AMI',
            'SelfReadOption' => 1,
            'PaymentMode' => 'RCCS',
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
            'ValueAddedService' => 'testAddOnServiceName',
            'CreatedBy' => 'testUsername',
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
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('123456');
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

    public function testContractApplicationRequestDataHandlerWithCustomerAsNull()
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

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['test@test.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactPointProphecy = $contactPointProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactPointProphecy]);
        $personProphecy->getHonorificPrefix()->willReturn('Mr.');
        $personProphecy = $personProphecy->reveal();

        $tariffRateProphecy = $this->prophesize(TariffRate::class);
        $tariffRateProphecy->getTariffRateNumber()->willReturn('SWPC123456');
        $tariffRateProphecy = $tariffRateProphecy->reveal();

        $supplementaryFilesProphecy = $this->prophesize(DigitalDocument::class);
        $supplementaryFilesProphecy->getContentPath()->willReturn('');
        $supplementaryFilesProphecy->getName()->willReturn('testAttachmentName');
        $supplementaryFilesProphecy->getUrl()->willReturn('');
        $supplementaryFilesProphecy = $supplementaryFilesProphecy->reveal();

        $addOnServiceProphecy = $this->prophesize(AddonService::class);
        $addOnServiceProphecy->getName()->willReturn('testAddOnServiceName');
        $addOnServiceProphecy = $addOnServiceProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getEmail()->willReturn(null);
        $userProphecy->getUsername()->willReturn('testUsername');
        $userProphecy = $userProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('SWAP123456');
        $applicationRequestProphecy->getAddonServices()->willReturn([$addOnServiceProphecy]);
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);
        $applicationRequestProphecy->getCustomer()->willReturn(null);
        $applicationRequestProphecy->getCreator()->willReturn($userProphecy);
        $applicationRequestProphecy->isCustomized()->willReturn(true);
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->isGIROOption()->willReturn(false);
        $applicationRequestProphecy->getBillSubscriptionTypes()->willReturn(['ELECTRONIC', 'HARDCOPY']);
        $applicationRequestProphecy->getContractType()->willReturn(new ContractType(ContractType::COMMERCIAL));
        $applicationRequestProphecy->getContractSubtype()->willReturn('CONDOMINIUM');
        $applicationRequestProphecy->getEbsAccountNumber()->willReturn('EBS123456');
        $applicationRequestProphecy->getMsslAccountNumber()->willReturn('MSSL123456');
        $applicationRequestProphecy->getMeterType()->willReturn(new MeterType(MeterType::AMI));
        $applicationRequestProphecy->getPersonDetails()->willReturn($personProphecy);
        $applicationRequestProphecy->getPreferredStartDate()->willReturn($now);
        $applicationRequestProphecy->getPromotion()->willReturn(null);
        $applicationRequestProphecy->getTariffRate()->willReturn($tariffRateProphecy);
        $applicationRequestProphecy->getSupplementaryFiles()->willReturn([$supplementaryFilesProphecy]);

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
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('123456');
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
}
