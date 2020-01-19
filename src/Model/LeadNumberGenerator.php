<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Lead;

class LeadNumberGenerator
{
    const LENGTH = 8;
    const PREFIX = 'L-';
    const TYPE = 'lead';

    /**
     * @var RunningNumberGenerator
     */
    private $runningNumberGenerator;

    /**
     * @var array|null
     */
    private $runningNumberParameters;

    /**
     * @var \DateTimeZone|null
     */
    private $timezone;

    /**
     * @param RunningNumberGenerator $runningNumberGenerator
     * @param array|null             $runningNumberParameters
     * @param string|null            $timezone
     */
    public function __construct(RunningNumberGenerator $runningNumberGenerator, ?array $runningNumberParameters = null, ?string $timezone = null)
    {
        $this->runningNumberGenerator = $runningNumberGenerator;
        $this->runningNumberParameters = $runningNumberParameters;
        if (null !== $timezone) {
            $this->timezone = new \DateTimeZone($timezone);
        }
    }

    /**
     * Generates a lead number.
     *
     * @param Lead $lead
     *
     * @return string
     */
    public function generate(Lead $lead)
    {
        $length = self::LENGTH;
        $prefixDateSuffix = '';
        $prefix = self::PREFIX;
        $series = $length;
        $type = self::TYPE;

        if (!empty($this->runningNumberParameters['lead_number_prefix'])) {
            $prefix = $this->runningNumberParameters['lead_number_prefix'];
        }

        if (!empty($this->runningNumberParameters['lead_number_length'])) {
            $length = (int) $this->runningNumberParameters['lead_number_length'];
        }

        if (!empty($this->runningNumberParameters['lead_number_series'])) {
            $series = $this->runningNumberParameters['lead_number_series'];
            $now = new \DateTime();
            $now->setTimezone($this->timezone);
            $prefixDateSuffix = $now->format($series);
        }

        if ($series === $length) {
            $numberPrefix = self::PREFIX;
        } else {
            $numberPrefix = $prefix.$prefixDateSuffix;
        }

        $nextNumber = $this->runningNumberGenerator->getNextNumber($type, (string) $series);
        $leadNumber = \sprintf('%s%s', $numberPrefix, \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

        return $leadNumber;
    }
}
