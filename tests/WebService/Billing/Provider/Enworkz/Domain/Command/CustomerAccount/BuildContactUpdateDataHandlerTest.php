<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 16/4/19
 * Time: 6:44 PM.
 */

namespace App\Tests\WebService\Billing\Provider\Enworkz\Domain\Command\CustomerAccount;

use App\Entity\ContactPoint;
use App\Entity\Contract;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\EmployeeRole;
use App\Entity\Person;
use App\Enum\AccountType;
use App\Enum\ContractStatus;
use App\Enum\Role;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\CustomerAccount\BuildContactUpdateData;
use App\WebService\Billing\Provider\Enworkz\Domain\Command\CustomerAccount\BuildContactUpdateDataHandler;
use App\WebService\Billing\Services\DataMapper;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;

class BuildContactUpdateDataHandlerTest extends TestCase
{
    public function testCustomerContactUpdateWithAccountTypeIsIndividualAndWithContract()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SWCC123456');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::ACTIVE));
        $contractProphecy = $contractProphecy->reveal();

        $contactProphecy = $this->prophesize(ContactPoint::class);
        $contactProphecy->getEmails()->willReturn(['test@test.com']);
        $contactProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactProphecy]);
        $personProphecy = $personProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getContracts()->willReturn([$contractProphecy]);

        $customerAccount = $customerAccountProphecy->reveal();

        $expectedCustomerContactNumberData = [
            'FRCContractNumber' => 'SWCC123456',
            'ContactName' => 'TESTPERSONNAME',
            'Phone' => '111111',
            'Fax' => null,
            'Email' => 'test@test.com',
            'Mobile' => '111111',
            'EffectiveDate' => $now->format('Ymd'),
        ];

        $buildContactUpdateData = new BuildContactUpdateData($customerAccount);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContactPoints([$contactProphecy])->willReturn([
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

        $buildContactUpdateDataHandler = new BuildContactUpdateDataHandler($dataMapperProphecy);
        $actualCustomerContactNumberData = $buildContactUpdateDataHandler->handle($buildContactUpdateData);

        $this->assertEquals($expectedCustomerContactNumberData, $actualCustomerContactNumberData);
    }

    public function testCustomerContactUpdateWithAccountTypeIsCorporateAndWithContract()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getContractNumber()->willReturn('SWCC123456');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::ACTIVE));
        $contractProphecy = $contractProphecy->reveal();

        $contactProphecy = $this->prophesize(ContactPoint::class);
        $contactProphecy->getEmails()->willReturn(['test@test.com']);
        $contactProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactProphecy]);
        $personProphecy = $personProphecy->reveal();

        $employeeCustomerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $employeeCustomerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $employeeCustomerAccountProphecy = $employeeCustomerAccountProphecy->reveal();

        $employeeProphecy = $this->prophesize(EmployeeRole::class);
        $employeeProphecy->getRoleName()->willReturn(new Role(Role::CONTACT_PERSON));
        $employeeProphecy->getEmployee()->willReturn($employeeCustomerAccountProphecy);
        $employeeProphecy = $employeeProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getName()->willReturn('testCorporationName');
        $corporationProphecy->getContactPoints()->willReturn([$contactProphecy]);
        $corporationProphecy->getEmployees()->willReturn([$employeeProphecy]);
        $corporationProphecy = $corporationProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::CORPORATE));
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporationProphecy);
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');
        $customerAccountProphecy->getContracts()->willReturn([$contractProphecy]);

        $customerAccount = $customerAccountProphecy->reveal();

        $expectedCustomerContactNumberData = [
            'FRCContractNumber' => 'SWCC123456',
            'ContactName' => 'TESTPERSONNAME',
            'Phone' => '111111',
            'Fax' => '111111',
            'Email' => 'test@test.com',
            'Mobile' => '111111',
            'EffectiveDate' => $now->format('Ymd'),
        ];

        $buildContactUpdateData = new BuildContactUpdateData($customerAccount);

        $dataMapperProphecy = $this->prophesize(DataMapper::class);
        $dataMapperProphecy->mapContactPoints([$contactProphecy])->willReturn([
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

        $buildContactUpdateDataHandler = new BuildContactUpdateDataHandler($dataMapperProphecy);
        $actualCustomerContactNumberData = $buildContactUpdateDataHandler->handle($buildContactUpdateData);

        $this->assertEquals($expectedCustomerContactNumberData, $actualCustomerContactNumberData);
    }
}
