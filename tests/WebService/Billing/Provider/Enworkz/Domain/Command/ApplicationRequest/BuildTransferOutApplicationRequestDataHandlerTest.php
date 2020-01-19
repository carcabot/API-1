<?php

declare(strict_types=1);

namespace App\Tests\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\Identification;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Enum\AccountType;
use App\Enum\IdentificationName;
use App\Enum\PostalAddressType;
use App\Enum\RefundType;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildTransferOutApplicationRequestData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\ApplicationRequest\BuildTransferOutApplicationRequestDataHandler;
use App\WebService\Billing\Services\DataMapper;
use PHPUnit\Framework\TestCase;

class BuildTransferOutApplicationRequestDataHandlerTest extends TestCase
{
    public function testRefundeeAccountTypeIsINDIVIDUAL()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValue()->willReturn('123');
        $identificationProphecy = $identificationProphecy->reveal();

        $personDetailsProphecy = $this->prophesize(Person::class);
        $personDetailsProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personDetailsProphecy->getName()->willReturn('shaboo personal');
        $personDetailsProphecy = $personDetailsProphecy->reveal();

        $refundeeProphecy = $this->prophesize(CustomerAccount::class);
        $refundeeProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $refundeeProphecy->getPersonDetails()->willReturn($personDetailsProphecy);
        $refundeeProphecy = $refundeeProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('CN123456789');
        $contractProphecy = $contractProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getRefundee()->willReturn($refundeeProphecy);
        $applicationRequestProphecy->getRefundeeDetails()->willReturn($personDetailsProphecy);
        $applicationRequestProphecy->getCustomer()->willReturn($refundeeProphecy);
        $applicationRequestProphecy->getPreferredEndDate()->willReturn($now);
        $applicationRequestProphecy->getContract()->willReturn($contractProphecy);
        $applicationRequestProphecy->getDepositRefundType()->willReturn(new RefundType(RefundType::FULL_REFUND));
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('ARN123456789');
        $applicationRequestProphecy->getRemark()->willReturn('shaboo remarks');
        $applicationRequestProphecy->getTerminationReason()->willReturn('bro, I have told you, it is not of your business');
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedTransferOutRequestData = [
            'CRMContractTransferOutNumber' => 'ARN123456789',
            'FRCContractNumber' => 'CN123456789',
            'RequestTransferOutDate' => $now->format('Ymd'),
            'Remarks' => 'shaboo remarks',
            'Deposit' => 'R',
            'DifferentPayeeIndicator' => false,
            'RefundPayeeName' => 'SHABOO PERSONAL',
            'RefundPayeeNRIC' => '123',
            'TerminationReason' => 'bro, I have told you, it is not of your business',
            'SelfReadOption' => 1,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
        ];

        $buildTransferOutApplicationRequestData = new BuildTransferOutApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapRefundType(new RefundType(RefundType::FULL_REFUND))->willReturn('R');
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('123');
        $dataMapperProphecy = $dataMapperProphecy->reveal();

        /**
         * @var BuildTransferOutApplicationRequestDataHandler
         */
        $buildTransferOutApplicationRequestDataHandler = new BuildTransferOutApplicationRequestDataHandler($dataMapperProphecy);
        $actualTransferOutRequestData = $buildTransferOutApplicationRequestDataHandler->handle($buildTransferOutApplicationRequestData);

        $this->assertEquals($expectedTransferOutRequestData, $actualTransferOutRequestData);
    }

    public function testRefundeeAccountTypeIsIndividualAndRefundeeIsNotCustomer()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValue()->willReturn('123');
        $identificationProphecy = $identificationProphecy->reveal();

        $personDetailsProphecy = $this->prophesize(Person::class);
        $personDetailsProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personDetailsProphecy->getName()->willReturn('shaboo personal');
        $personDetailsProphecy = $personDetailsProphecy->reveal();

        $refundeeProphecy = $this->prophesize(CustomerAccount::class);
        $refundeeProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $refundeeProphecy->getPersonDetails()->willReturn($personDetailsProphecy);
        $refundeeProphecy = $refundeeProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('CN123456789');
        $contractProphecy = $contractProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getRefundee()->willReturn(null);
        $applicationRequestProphecy->getRefundeeDetails()->willReturn($personDetailsProphecy);
        $applicationRequestProphecy->getCustomer()->willReturn($refundeeProphecy);
        $applicationRequestProphecy->getPreferredEndDate()->willReturn($now);
        $applicationRequestProphecy->getContract()->willReturn($contractProphecy);
        $applicationRequestProphecy->getDepositRefundType()->willReturn(new RefundType(RefundType::FULL_REFUND));
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('ARN123456789');
        $applicationRequestProphecy->getRemark()->willReturn('shaboo remarks');
        $applicationRequestProphecy->getTerminationReason()->willReturn('bro, I have told you, it is not of your business');
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedTransferOutRequestData = [
            'CRMContractTransferOutNumber' => 'ARN123456789',
            'FRCContractNumber' => 'CN123456789',
            'RequestTransferOutDate' => $now->format('Ymd'),
            'Remarks' => 'shaboo remarks',
            'Deposit' => 'R',
            'DifferentPayeeIndicator' => true,
            'RefundPayeeName' => 'SHABOO PERSONAL',
            'RefundPayeeNRIC' => '123',
            'TerminationReason' => 'bro, I have told you, it is not of your business',
            'SelfReadOption' => 1,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
        ];

        $buildTransferOutApplicationRequestData = new BuildTransferOutApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapRefundType(new RefundType(RefundType::FULL_REFUND))->willReturn('R');
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('123');
        $dataMapperProphecy = $dataMapperProphecy->reveal();
        /**
         * @var BuildTransferOutApplicationRequestDataHandler
         */
        $buildTransferOutApplicationRequestDataHandler = new BuildTransferOutApplicationRequestDataHandler($dataMapperProphecy);
        $actualTransferOutRequestData = $buildTransferOutApplicationRequestDataHandler->handle($buildTransferOutApplicationRequestData);

        $this->assertEquals($expectedTransferOutRequestData, $actualTransferOutRequestData);
    }

    public function testRefundeeAccountTypeIsIndividualWithSomeNullValues()
    {
        $identificationProphecy = $this->prophesize(Identification::class);
        $identificationProphecy->getName()->willReturn(new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD));
        $identificationProphecy->getValue()->willReturn('123');
        $identificationProphecy = $identificationProphecy->reveal();

        $personDetailsProphecy = $this->prophesize(Person::class);
        $personDetailsProphecy->getIdentifiers()->willReturn([$identificationProphecy]);
        $personDetailsProphecy->getName()->willReturn('shaboo personal');
        $personDetailsProphecy = $personDetailsProphecy->reveal();

        $refundeeProphecy = $this->prophesize(CustomerAccount::class);
        $refundeeProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $refundeeProphecy->getPersonDetails()->willReturn($personDetailsProphecy);
        $refundeeProphecy = $refundeeProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('CN123456789');
        $contractProphecy = $contractProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy = $postalAddressProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getRefundee()->willReturn(null);
        $applicationRequestProphecy->getRefundeeDetails()->willReturn(null);
        $applicationRequestProphecy->getCustomer()->willReturn($refundeeProphecy);
        $applicationRequestProphecy->getPreferredEndDate()->willReturn(null);
        $applicationRequestProphecy->getContract()->willReturn(null);
        $applicationRequestProphecy->getDepositRefundType()->willReturn(null);
        $applicationRequestProphecy->getApplicationRequestNumber()->willReturn('ARN123456789');
        $applicationRequestProphecy->getRemark()->willReturn('shaboo remarks');
        $applicationRequestProphecy->getTerminationReason()->willReturn('bro, I have told you, it is not of your business');
        $applicationRequestProphecy->isSelfReadMeterOption()->willReturn(true);
        $applicationRequestProphecy->getAddresses()->willReturn([$postalAddressProphecy]);

        $applicationRequest = $applicationRequestProphecy->reveal();

        $expectedTransferOutRequestData = [
            'CRMContractTransferOutNumber' => 'ARN123456789',
            'FRCContractNumber' => null,
            'RequestTransferOutDate' => null,
            'Remarks' => 'shaboo remarks',
            'Deposit' => null,
            'DifferentPayeeIndicator' => true,
            'RefundPayeeName' => null,
            'RefundPayeeNRIC' => null,
            'TerminationReason' => 'bro, I have told you, it is not of your business',
            'SelfReadOption' => 1,
            'PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345',
        ];

        $buildTransferOutApplicationRequestData = new BuildTransferOutApplicationRequestData($applicationRequest);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapAddressFields($postalAddressProphecy)->willReturn(['PremisesAddress' => '123',
            'PremisesUnitNumber' => '#1',
            'PremisesPostalCode' => '12345', ]);
        $dataMapperProphecy->mapIdentifierByKey([$identificationProphecy], new IdentificationName(IdentificationName::NATIONAL_REGISTRATION_IDENTITY_CARD))->willReturn('123');
        $dataMapperProphecy = $dataMapperProphecy->reveal();
        /**
         * @var BuildTransferOutApplicationRequestDataHandler
         */
        $buildTransferOutApplicationRequestDataHandler = new BuildTransferOutApplicationRequestDataHandler($dataMapperProphecy);
        $actualTransferOutRequestData = $buildTransferOutApplicationRequestDataHandler->handle($buildTransferOutApplicationRequestData);

        $this->assertEquals($expectedTransferOutRequestData, $actualTransferOutRequestData);
    }
}
