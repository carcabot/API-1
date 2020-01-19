<?php

declare(strict_types=1);

namespace App\Domain\Command\Payment;

use App\Entity\Payment;

/**
 * Updates payment number.
 */
class UpdatePaymentNumber
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @param Payment $payment
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Gets the payment.
     *
     * @return Payment
     */
    public function getPayment(): Payment
    {
        return $this->payment;
    }
}
