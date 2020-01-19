<?php

declare(strict_types=1);

namespace App\Domain\Command\Payment;

use App\Model\PaymentNumberGenerator;

class UpdatePaymentNumberHandler
{
    /**
     * @var PaymentNumberGenerator
     */
    private $paymentNumberGenerator;

    /**
     * @param PaymentNumberGenerator $paymentNumberGenerator
     */
    public function __construct(PaymentNumberGenerator $paymentNumberGenerator)
    {
        $this->paymentNumberGenerator = $paymentNumberGenerator;
    }

    public function handle(UpdatePaymentNumber $command): void
    {
        $payment = $command->getPayment();
        $paymentNumber = $this->paymentNumberGenerator->generate($payment);

        $payment->setPaymentNumber($paymentNumber);
    }
}
