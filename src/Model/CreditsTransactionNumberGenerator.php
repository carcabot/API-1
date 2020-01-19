<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\CreditsTransaction;

class CreditsTransactionNumberGenerator
{
    const LENGTH = 8;
    const PREFIX = 'TX';
    const TYPE = 'credits_transaction';

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

    public function generate(CreditsTransaction $creditsTransaction)
    {
        $length = self::LENGTH;
        $prefix = self::PREFIX;

        $nextNumber = $this->runningNumberGenerator->getNextNumber(self::TYPE, (string) $length);
        $creditsTransactionNumber = \sprintf('%s%s', $prefix, \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

        return $creditsTransactionNumber;
    }
}
