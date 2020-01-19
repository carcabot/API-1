<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\QuantitativeValue;
use App\Entity\Ticket;
use App\Entity\TicketServiceLevelAgreement;

class TicketProcessor
{
    /**
     * @var WorkingHourCalculator
     */
    private $workingHourCalculator;

    public function __construct(WorkingHourCalculator $workingHourCalculator)
    {
        $this->workingHourCalculator = $workingHourCalculator;
    }

    public function getPlannedCompletionDate(Ticket $ticket, TicketServiceLevelAgreement $serviceLevelAgreement)
    {
        if (null !== $ticket->getDateOpened()) {
            $date = clone $ticket->getDateOpened();
        } else {
            return null;
        }

        return $this->magicFunction($date, $serviceLevelAgreement->getTimer(), $serviceLevelAgreement->getOperationExclusions());
    }

    /**
     * @param \DateTime         $date
     * @param QuantitativeValue $duration
     * @param array             $openingHours
     * @param bool              $today
     *
     * @return \DateTime|null
     */
    private function magicFunction(\DateTime $date, QuantitativeValue $duration, array $openingHours, bool $today = true)
    {
        $durationValue = $this->getMinutesFromDuration($duration);

        if ($durationValue > 0) {
            list($workingMinutes, $startTimestamp, $endTimestamp) = $this->workingHourCalculator->getWorkingMinutes($date, $openingHours, $today);
            $endDate = new \DateTime();
            $endDate->setTimestamp($endTimestamp);
            $diff = $durationValue;

            if ($workingMinutes > 0) {
                $diff = $durationValue - $workingMinutes;
                // means that it ends here
                if ($diff <= 0) {
                    $startDate = new \DateTime();
                    $startDate->setTimestamp($startTimestamp);
                    $startDate->modify('+'.$durationValue.' minutes');

                    return $startDate;
                }
            }

            return $this->magicFunction($endDate, new QuantitativeValue((string) $diff, null, null, 'MIN'), $openingHours, false);
        }

        return null;
    }

    private function getMinutesFromDuration(QuantitativeValue $duration)
    {
        if (null === $duration->getValue()) {
            return 0;
        }

        // default is hours
        switch ($duration->getUnitCode()) {
            case 'DAY':
                return (int) ($duration->getValue() * 24 * 60);
                break;
            case 'MIN':
                return (int) $duration->getValue();
                break;
            default:
                return (int) ($duration->getValue() * 60);
        }
    }
}
