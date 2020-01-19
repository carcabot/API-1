<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\CustomerAccount;
use App\Enum\AccountCategory;
use App\Model\CustomerAccountNumberGenerator;
use App\Model\RunningNumberGenerator;
use PHPUnit\Framework\TestCase;

class CustomerAccountNumberGeneratorTest extends TestCase
{
    public function testGenerateAccountNumberForCustomer()
    {
        $length = 8;
        $series = $length;
        $prefix = 'C-';
        $type = 'customer_account';
        $parameters = [];
        $timezone = 'Asia/Singapore';

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn(['CUSTOMER']);
        $customerAccount = $customerAccountProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($prefix, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $customerAccountNumberGenerator = new CustomerAccountNumberGenerator($timezone, $parameters, $runningNumberGenerator);
        $accountNumber = $customerAccountNumberGenerator->generate($customerAccount);

        $this->assertEquals($accountNumber, 'C-00000001');
    }

    public function testGenerateAccountNumberForUser()
    {
        $length = 8;
        $series = $length;
        $prefix = 'U-';
        $type = 'user_account';
        $parameters = [];
        $timezone = 'Asia/Singapore';

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn([]);
        $customerAccount = $customerAccountProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($prefix, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $customerAccountNumberGenerator = new CustomerAccountNumberGenerator($timezone, $parameters, $runningNumberGenerator);
        $accountNumber = $customerAccountNumberGenerator->generate($customerAccount);

        $this->assertEquals($accountNumber, 'U-00000001');
    }

    public function testGenerateAccountNumberForPartner()
    {
        $timezone = 'Asia/Singapore';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($timezone));

        $parameters = [
            'partner_number_prefix' => 'SWPS',
            'partner_number_length' => '6',
            'partner_number_series' => 'ym',
        ];

        $series = $now->format($parameters['partner_number_series']);

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn([AccountCategory::PARTNER]);
        $customerAccount = $customerAccountProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($parameters['partner_number_prefix'], $parameters['partner_number_series'])->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $customerAccountNumberGenerator = new CustomerAccountNumberGenerator($timezone, $parameters, $runningNumberGenerator);
        $accountNumber = $customerAccountNumberGenerator->generate($customerAccount);

        $prefixDateSuffix = $now->format('ym');
        $this->assertEquals($accountNumber, 'SWPS'.$prefixDateSuffix.'000001');
    }

    public function testGenerateAccountNumberForSalesRepresentative()
    {
        $timezone = 'Asia/Singapore';
        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($timezone));

        $parameters = [
            'partner_number_prefix' => 'SWPS',
            'partner_number_length' => '6',
            'partner_number_series' => 'ym',
        ];

        $series = $now->format($parameters['partner_number_series']);

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn([AccountCategory::SALES_REPRESENTATIVE]);
        $customerAccount = $customerAccountProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $customerAccountNumberGenerator = new CustomerAccountNumberGenerator($timezone, $parameters, $runningNumberGenerator);
        $accountNumber = $customerAccountNumberGenerator->generate($customerAccount);

        $this->assertEquals($accountNumber, null);
    }

    public function testGenerateAccountNumberForPartnerContactPerson()
    {
        $length = 8;
        $series = $length;
        $prefix = 'PC-';
        $type = 'partner_contact_person';
        $parameters = [];
        $timezone = 'Asia/Singapore';

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn([AccountCategory::PARTNER_CONTACT_PERSON]);
        $customerAccount = $customerAccountProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($prefix, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $customerAccountNumberGenerator = new CustomerAccountNumberGenerator($timezone, $parameters, $runningNumberGenerator);
        $accountNumber = $customerAccountNumberGenerator->generate($customerAccount);

        $this->assertEquals($accountNumber, 'PC-00000001');
    }

    public function testGenerateAccountNumberForPartnerDefault()
    {
        $length = 8;
        $series = $length;
        $prefix = 'P-';
        $type = 'partner';
        $parameters = [];
        $timezone = 'Asia/Singapore';

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn([AccountCategory::PARTNER]);
        $customerAccount = $customerAccountProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($prefix, $series)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $customerAccountNumberGenerator = new CustomerAccountNumberGenerator($timezone, $parameters, $runningNumberGenerator);
        $accountNumber = $customerAccountNumberGenerator->generate($customerAccount);

        $this->assertEquals($accountNumber, 'P-00000001');
    }
}
