<?php

declare(strict_types=1);

namespace App\Model;

class OfferNumberGenerator
{
    const LENGTH = 9;
    const PREFIX = 'O';
    const TYPE = 'offer';

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
     * Generates an order number.
     *
     * @return string
     */
    public function generate()
    {
        $length = self::LENGTH;
        $prefix = self::PREFIX;

        $nextNumber = $this->runningNumberGenerator->getNextNumber(self::TYPE, (string) $length);
        $offerNumber = \sprintf('%s%s', $prefix, \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

        return $offerNumber;
    }
}
