<?php

declare(strict_types=1);

namespace App\Model;

class StatementNumberGenerator
{
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
     * Generates statement number.
     *
     * @param string $prefix
     * @param string $type
     * @param int    $length
     *
     * @return string
     */
    public function generate(string $prefix, string $type, int $length)
    {
        $nextNumber = $this->runningNumberGenerator->getNextNumber($type, (string) $length);
        $statementNumber = \sprintf('%s%s', $prefix, \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

        return $statementNumber;
    }
}
