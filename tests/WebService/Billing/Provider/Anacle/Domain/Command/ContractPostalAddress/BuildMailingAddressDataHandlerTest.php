<?php

declare(strict_types=1);

namespace App\Tests\WebService\Billing\Provider\Anacle\Domain\Command\ContractPostalAddress;

use App\Entity\ContactPoint;
use App\Entity\Contract;
use App\Entity\ContractPostalAddress;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use App\Entity\PostalAddress;
use App\Enum\AccountType;
use App\Enum\PostalAddressType;
use App\WebService\Billing\Provider\Anacle\Domain\Command\ContractPostalAddress as ContractPostalAddressCommand;
use App\WebService\Billing\Services\DataMapper;
use PHPUnit\Framework\TestCase;

class BuildMailingAddressDataHandlerTest extends TestCase
{
    public function testAccountTypeIsCorporate()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['shaboo@email.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([]);
        $contactPointProphecy->getFaxNumbers()->willReturn([]);
        $contactPoint = $contactPointProphecy->reveal();

        $corporationDetailsProphecy = $this->prophesize(Corporation::class);
        $corporationDetailsProphecy->getName()->willReturn('Shaboo SDN BHD');
        $corporationDetailsProphecy->getContactPoints()->willReturn([$contactPoint]);
        $corporationDetails = $corporationDetailsProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporationDetails);
        $customerAccount = $customerAccountProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW4815162342');
        $contractProphecy->getCustomer()->willReturn($customerAccount);
        $contract = $contractProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::MAILING_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy->getStreetAddress()->willReturn('Jalan Shaboo');
        $postalAddressProphecy->getUnitNumber()->willReturn('2');
        $postalAddressProphecy->getBuildingName()->willReturn('Shaboo');
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddressProphecy->getContract()->willReturn($contract);
        $contractPostalAddressProphecy->getValidFrom()->willReturn($now);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContactPoints([$contactPoint])->willReturn([
            'email' => 'shaboo@email.com',
        ]);
        $dataMapper = $dataMapperProphecy->reveal();
        $expectedData = [
            'CustomerAccountNumber' => 'SW4815162342',
            'AttendTo' => 'SHABOO SDN BHD',
            'EmailAddress' => 'shaboo@email.com',
            'EffectiveDate' => $now->format('Ymd'),
            'AddressCountry' => 'SY',
            'AddressState' => 'ASIA',
            'AddressCity' => 'ASIA',
            'AddressLine1' => '123 JALAN SHABOO',
            'AddressLine2' => 'Shaboo',
            'AddressLine3' => '#1-2',
            'PostalCode' => '12345',
        ];

        $buildMailingAddressData = new ContractPostalAddressCommand\BuildMailingAddressData($contractPostalAddress);
        $buildMailingAddressDataHandler = new ContractPostalAddressCommand\BuildMailingAddressDataHandler($dataMapper);
        $actualData = $buildMailingAddressDataHandler->handle($buildMailingAddressData);

        $this->assertEquals($actualData, $expectedData);
    }

    public function testAccountTypeIsIndividual()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['shaboo@email.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([]);
        $contactPointProphecy->getFaxNumbers()->willReturn([]);
        $contactPoint = $contactPointProphecy->reveal();

        $personDetailsProphecy = $this->prophesize(Person::class);
        $personDetailsProphecy->getName()->willReturn('Mohammad Shaban');
        $personDetailsProphecy->getContactPoints()->willReturn([$contactPoint]);
        $personDetails = $personDetailsProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy->getPersonDetails()->willReturn($personDetails);
        $customerAccount = $customerAccountProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW4815162342');
        $contractProphecy->getCustomer()->willReturn($customerAccount);
        $contract = $contractProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::MAILING_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy->getStreetAddress()->willReturn('Jalan Shaboo');
        $postalAddressProphecy->getUnitNumber()->willReturn('2');
        $postalAddressProphecy->getBuildingName()->willReturn('Shaboo');
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddressProphecy->getContract()->willReturn($contract);
        $contractPostalAddressProphecy->getValidFrom()->willReturn($now);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContactPoints([$contactPoint])->willReturn([
            'email' => 'shaboo@email.com',
        ]);
        $dataMapper = $dataMapperProphecy->reveal();
        $expectedData = [
            'CustomerAccountNumber' => 'SW4815162342',
            'AttendTo' => 'MOHAMMAD SHABAN',
            'EmailAddress' => 'shaboo@email.com',
            'EffectiveDate' => $now->format('Ymd'),
            'AddressCountry' => 'SY',
            'AddressState' => 'ASIA',
            'AddressCity' => 'ASIA',
            'AddressLine1' => '123 JALAN SHABOO',
            'AddressLine2' => 'Shaboo',
            'AddressLine3' => '#1-2',
            'PostalCode' => '12345',
        ];

        $buildMailingAddressData = new ContractPostalAddressCommand\BuildMailingAddressData($contractPostalAddress);
        $buildMailingAddressDataHandler = new ContractPostalAddressCommand\BuildMailingAddressDataHandler($dataMapper);
        $actualData = $buildMailingAddressDataHandler->handle($buildMailingAddressData);

        $this->assertEquals($actualData, $expectedData);
    }

    public function testAddressTypeIsNotMailing()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $contactPointProphecy = $this->prophesize(ContactPoint::class);
        $contactPointProphecy->getEmails()->willReturn(['shaboo@email.com']);
        $contactPointProphecy->getMobilePhoneNumbers()->willReturn([]);
        $contactPointProphecy->getTelephoneNumbers()->willReturn([]);
        $contactPointProphecy->getFaxNumbers()->willReturn([]);
        $contactPoint = $contactPointProphecy->reveal();

        $personDetailsProphecy = $this->prophesize(Person::class);
        $personDetailsProphecy->getName()->willReturn('Mohammad Shaban');
        $personDetailsProphecy->getContactPoints()->willReturn([$contactPoint]);
        $personDetails = $personDetailsProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy->getPersonDetails()->willReturn($personDetails);
        $customerAccount = $customerAccountProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SW4815162342');
        $contractProphecy->getCustomer()->willReturn($customerAccount);
        $contract = $contractProphecy->reveal();

        $postalAddressProphecy = $this->prophesize(PostalAddress::class);
        $postalAddressProphecy->getType()->willReturn(new PostalAddressType(PostalAddressType::PREMISE_ADDRESS));
        $postalAddressProphecy->getHouseNumber()->willReturn('123');
        $postalAddressProphecy->getFloor()->willReturn('1');
        $postalAddressProphecy->getAddressCountry()->willReturn('SY');
        $postalAddressProphecy->getAddressRegion()->willReturn('ASIA');
        $postalAddressProphecy->getPostalCode()->willReturn('12345');
        $postalAddressProphecy->getStreetAddress()->willReturn('Jalan Shaboo');
        $postalAddressProphecy->getUnitNumber()->willReturn('2');
        $postalAddressProphecy->getBuildingName()->willReturn('Shaboo');
        $postalAddress = $postalAddressProphecy->reveal();

        $contractPostalAddressProphecy = $this->prophesize(ContractPostalAddress::class);
        $contractPostalAddressProphecy->getAddress()->willReturn($postalAddress);
        $contractPostalAddressProphecy->getContract()->willReturn($contract);
        $contractPostalAddressProphecy->getValidFrom()->willReturn($now);
        $contractPostalAddress = $contractPostalAddressProphecy->reveal();

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContactPoints([$contactPoint])->willReturn([
            'email' => 'shaboo@email.com',
        ]);
        $dataMapper = $dataMapperProphecy->reveal();
        $expectedData = [];

        $buildMailingAddressData = new ContractPostalAddressCommand\BuildMailingAddressData($contractPostalAddress);
        $buildMailingAddressDataHandler = new ContractPostalAddressCommand\BuildMailingAddressDataHandler($dataMapper);
        $actualData = $buildMailingAddressDataHandler->handle($buildMailingAddressData);

        $this->assertEquals($actualData, $expectedData);
    }
}
