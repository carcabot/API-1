<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 16/4/19
 * Time: 12:34 PM.
 */

namespace App\Tests\WebService\Billing\Provider\Anacle\Domain\Command\CustomerAccount;

use App\Entity\ContactPoint;
use App\Entity\CustomerAccount;
use App\Entity\Person;
use App\Enum\AccountType;
use App\WebService\Billing\Provider\Anacle\Domain\Command\CustomerAccount\BuildContactUpdateData;
use App\WebService\Billing\Provider\Anacle\Domain\Command\CustomerAccount\BuildContactUpdateDataHandler;
use App\WebService\Billing\Services\DataMapper;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;

class BuildContactUpdateDataHandlerTest extends TestCase
{
    public function testCustomerContactUpdateWithAccountTypeIsIndividual()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactProphecy = $this->prophesize(ContactPoint::class);
        $contactProphecy->getEmails()->willReturn(['test@test.com']);
        $contactProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy = $contactProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactProphecy]);
        $personProphecy = $personProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');

        $customerAccount = $customerAccountProphecy->reveal();
        $previousAccountName = 'testPreviousName';

        $expectedCustomerContactNumberData = [
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'Contacts' => [
                'Contact' => [
                    'PreviousContactName' => 'testPreviousName',
                    'ContactName' => 'TESTPERSONNAME',
                    'Phone' => '111111',
                    'Fax' => '111111',
                    'Email' => 'test@test.com',
                    'Mobile' => '111111',
                    'EffectiveDate' => $now->format('Ymd'),
                ],
            ],
        ];

        $buildContactUpdateData = new BuildContactUpdateData($customerAccount, $previousAccountName);

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

    public function testCustomerContactUpdateWithCustomerPreviousNameNull()
    {
        $now = new \DateTime();
        $timezone = new \DateTimeZone('Asia/Singapore');
        $now->setTimezone($timezone);

        $phoneNumberProphecy = $this->prophesize(PhoneNumber::class);
        $phoneNumberProphecy->getCountryCode()->willReturn('00');
        $phoneNumberProphecy->getNationalNumber()->willReturn('111111');
        $phoneNumberProphecy = $phoneNumberProphecy->reveal();

        $contactProphecy = $this->prophesize(ContactPoint::class);
        $contactProphecy->getEmails()->willReturn(['test@test.com']);
        $contactProphecy->getMobilePhoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy->getTelephoneNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy->getFaxNumbers()->willReturn([$phoneNumberProphecy]);
        $contactProphecy = $contactProphecy->reveal();

        $personProphecy = $this->prophesize(Person::class);
        $personProphecy->getName()->willReturn('testPersonName');
        $personProphecy->getContactPoints()->willReturn([$contactProphecy]);
        $personProphecy = $personProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getType()->willReturn(new AccountType(AccountType::INDIVIDUAL));
        $customerAccountProphecy->getPersonDetails()->willReturn($personProphecy);
        $customerAccountProphecy->getAccountNumber()->willReturn('SWCC123456');

        $customerAccount = $customerAccountProphecy->reveal();
        $previousAccountName = null;

        $expectedCustomerContactNumberData = [
            'CRMCustomerReferenceNumber' => 'SWCC123456',
            'Contacts' => [
                'Contact' => [
                    'PreviousContactName' => 'TESTPERSONNAME',
                    'ContactName' => 'TESTPERSONNAME',
                    'Phone' => '111111',
                    'Fax' => '111111',
                    'Email' => 'test@test.com',
                    'Mobile' => '111111',
                    'EffectiveDate' => $now->format('Ymd'),
                ],
            ],
        ];

        $buildContactUpdateData = new BuildContactUpdateData($customerAccount, $previousAccountName);

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
