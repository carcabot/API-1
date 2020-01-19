<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Campaign;

class CampaignNumberGenerator
{
    const LENGTH = 9;
    const PREFIX = 'C';
    const TYPE = 'campaign';

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
     * Generates campaign number.
     *
     * @param Campaign $campaign
     *
     * @return string
     */
    public function generate(Campaign $campaign)
    {
        $nextNumber = $this->runningNumberGenerator->getNextNumber(self::TYPE, (string) self::LENGTH);
        $campaignNumber = \sprintf('%s%s', self::PREFIX, \str_pad((string) $nextNumber, self::LENGTH, '0', STR_PAD_LEFT));

        return $campaignNumber;
    }
}
