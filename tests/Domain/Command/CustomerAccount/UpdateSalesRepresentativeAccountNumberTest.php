<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\CustomerAccount;

use App\Domain\Command\CustomerAccount\UpdateSalesRepresentativeAccountNumber;
use App\Domain\Command\CustomerAccount\UpdateSalesRepresentativeAccountNumberHandler;
use App\Entity\CustomerAccount;
use App\Model\RunningNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdateSalesRepresentativeAccountNumberTest extends TestCase
{
    public function testUpdateAccountNumberForSalesRepresentative()
    {
        $prefix = 'MCD';

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getAccountNumber()->willReturn($prefix);
        $customerAccount = $customerAccountProphecy->reveal();

        $salesRepresentativeCustomerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $salesRepresentativeCustomerAccountProphecy->setAccountNumber('MCD00001')->shouldBeCalled();
        $salesRepresentativeCustomerAccount = $salesRepresentativeCustomerAccountProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($customerAccount->getAccountNumber(), $customerAccount->getAccountNumber())->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $updateAccountNumberHandler = new UpdateSalesRepresentativeAccountNumberHandler($runningNumberGenerator);
        $updateAccountNumberHandler->handle(new UpdateSalesRepresentativeAccountNumber($customerAccount, $salesRepresentativeCustomerAccount));
    }
}
