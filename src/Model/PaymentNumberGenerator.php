<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Payment;

class PaymentNumberGenerator
{
    const LENGTH = 8;
    const PREFIX = 'PY';
    const TYPE = 'payment';
    /**
     * @var RunningNumberGenerator
     */
    private $runningNumberGenerator;

    /**
     * @param RunningNumberGenerator $runningNumberGenerator
     */
    public function __construct(RunningNumberGenerator $runningNumberGenerator)
    {
        $this->runningNumberGenerator = $runningNumberGenerator;
    }

    /**
     * Generates payment number.
     *
     * @param Payment $payment
     *
     * @return string
     */
    public function generate(Payment $payment)
    {
        $nextNumber = $this->runningNumberGenerator->getNextNumber(self::TYPE, (string) self::LENGTH);
        $paymentNumber = \sprintf('%s%s', self::PREFIX, \str_pad((string) $nextNumber, self::LENGTH, '0', STR_PAD_LEFT));

        return $paymentNumber;
    }
}
