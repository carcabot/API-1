<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Payment;
use App\Model\PaymentNumberGenerator;
use App\Model\RunningNumberGenerator;
use PHPUnit\Framework\TestCase;

class PaymentNumberGeneratorTest extends TestCase
{
    public function testGeneratePaymentNumber()
    {
        $length = 8;
        $prefix = 'PY';
        $type = 'payment';
        $number = 1;

        $paymentProphecy = $this->prophesize(Payment::class);
        $payment = $paymentProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $paymentNumberGenerator = new PaymentNumberGenerator($runningNumberGenerator);
        $paymentNumber = $paymentNumberGenerator->generate($payment);

        $this->assertEquals($paymentNumber, 'PY00000001');
    }
}
