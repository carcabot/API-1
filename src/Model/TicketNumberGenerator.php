<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Ticket;

class TicketNumberGenerator
{
    const LENGTH = 9;
    const PREFIX = 'T';
    const TYPE = 'ticket';

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
     * Generates a ticket number.
     *
     * @param Ticket $ticket
     *
     * @throws \Exception
     *
     * @return string
     */
    public function generate(Ticket $ticket)
    {
        $length = self::LENGTH;
        $prefixDateSuffix = '';
        $prefix = self::PREFIX;
        $series = $length;
        $type = self::TYPE;

        if (!empty($this->runningNumberParameters['ticket_prefix'])) {
            $prefix = $this->runningNumberParameters['ticket_prefix'];
        }

        if (!empty($this->runningNumberParameters['ticket_length'])) {
            $length = (int) $this->runningNumberParameters['ticket_length'];
        }

        if (!empty($this->runningNumberParameters['ticket_series'])) {
            $series = $this->runningNumberParameters['ticket_series'];
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
        $ticketNumber = \sprintf('%s%s', $numberPrefix, \str_pad((string) $nextNumber, $length, '0', STR_PAD_LEFT));

        return $ticketNumber;
    }
}
