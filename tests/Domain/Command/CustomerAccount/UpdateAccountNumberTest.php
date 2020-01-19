<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\CustomerAccount;

use App\Domain\Command\CustomerAccount\UpdateAccountNumber;
use App\Domain\Command\CustomerAccount\UpdateAccountNumberHandler;
use App\Entity\CustomerAccount;
use App\Model\CustomerAccountNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdateAccountNumberTest extends TestCase
{
    public function testUpdateAccountNumberForUser()
    {
        $length = 8;
        $prefix = 'C-';
        $type = 'customer_account';
        $number = 1;

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->setAccountNumber('C-00000001')->shouldBeCalled();
        $customerAccount = $customerAccountProphecy->reveal();

        $customerAccountNumberGeneratorProphecy = $this->prophesize(CustomerAccountNumberGenerator::class);
        $customerAccountNumberGeneratorProphecy->generate($customerAccount)->willReturn(\sprintf('%s%s', $prefix, \str_pad((string) $number, $length, '0', STR_PAD_LEFT)));
        $customerAccountNumberGenerator = $customerAccountNumberGeneratorProphecy->reveal();

        $updateAccountNumberHandler = new UpdateAccountNumberHandler($customerAccountNumberGenerator);
        $updateAccountNumberHandler->handle(new UpdateAccountNumber($customerAccount));
    }
}
