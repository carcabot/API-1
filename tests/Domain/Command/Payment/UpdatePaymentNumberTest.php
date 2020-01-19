<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\Payment;

use App\Domain\Command\Payment\UpdatePaymentNumber;
use App\Domain\Command\Payment\UpdatePaymentNumberHandler;
use App\Entity\Payment;
use App\Model\PaymentNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdatePaymentNumberTest extends TestCase
{
    public function testUpdatePaymentNumber()
    {
        $length = 8;
        $prefix = 'PY';
        $type = 'payment';
        $number = 1;

        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->setPaymentNumber('PY00000001')->shouldBeCalled();
        $payment = $paymentProphecy->reveal();

        $paymentNumberGeneratorProphecy = $this->prophesize(PaymentNumberGenerator::class);
        $paymentNumberGeneratorProphecy->generate($payment)->willReturn(\sprintf('%s%s', $prefix, \str_pad((string) $number, $length, '0', STR_PAD_LEFT)));
        $paymentNumberGenerator = $paymentNumberGeneratorProphecy->reveal();

        $updatePaymentNumberHandler = new UpdatePaymentNumberHandler($paymentNumberGenerator);
        $updatePaymentNumberHandler->handle(new UpdatePaymentNumber($payment));
    }
}
