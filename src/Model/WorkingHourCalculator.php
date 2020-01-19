<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\CountryCalendar;
use App\Entity\Ticket;
use App\Enum\TicketStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

class WorkingHourCalculator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var OpeningHoursSpecificationProcessor
     */
    private $openingHoursSpecificationProcessor;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface             $entityManager
     * @param OpeningHoursSpecificationProcessor $openingHoursSpecificationProcessor
     * @param string                             $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, OpeningHoursSpecificationProcessor $openingHoursSpecificationProcessor, string $timezone)
    {
        $this->entityManager = $entityManager;
        $this->openingHoursSpecificationProcessor = $openingHoursSpecificationProcessor;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @param int|null  $totalHour
     *
     * @return mixed
     */
    public function calculateTotalWorkingDays(\DateTime $fromDate, \DateTime $toDate, ?int $totalHour)
    {
        $em = $this->entityManager;

        if (null !== $totalHour) {
            $hours = $totalHour;
        } else {
            $hours = 0;
        }

        $countryCalendar = $em->getRepository(CountryCalendar::class)->createQueryBuilder('cc');

        $openHours = $countryCalendar->select('cc')
            ->where($countryCalendar->expr()->eq('cc.enabled', ':enabled'))
            ->setParameters(['enabled' => true])
            ->getQuery()
            ->getOneOrNullResult(Query::HYDRATE_OBJECT);
        if (null === $openHours) {
            $result['hours'] = 0;
        } else {
            $result = $this->calculateWorkingHourByInterval($fromDate, $toDate, $openHours->getOpeningHours(), $hours, 1, 'day', true);
            $result = $this->calculateWorkingHourByInterval($result['existingDate'], $toDate, $openHours->getOpeningHours(), $result['hours'], 1, 'hours', true);
            $result = $this->calculateWorkingHourByInterval($result['existingDate'], $toDate, $openHours->getOpeningHours(), $result['hours'], 1, 'minutes', true);
        }

        return $result['hours'];
    }

    /**
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @param int|null  $totalHour
     *
     * @return mixed
     */
    public function calculateTotalWorkingHoursPerDay(\DateTime $fromDate, \DateTime $toDate, ?int $totalHour)
    {
        $em = $this->entityManager;

        if (null !== $totalHour) {
            $hours = $totalHour;
        } else {
            $hours = 0;
        }

        $countryCalendar = $em->getRepository(CountryCalendar::class)->createQueryBuilder('cc');

        $openHours = $countryCalendar->select('cc')
            ->where($countryCalendar->expr()->eq('cc.enabled', ':enabled'))
            ->setParameters(['enabled' => true])
            ->getQuery()
            ->getOneOrNullResult(Query::HYDRATE_OBJECT);
        if (null === $openHours) {
            $result['hours'] = 0;
        } else {
            $result = $this->calculateWorkingHourByInterval($fromDate, $toDate, $openHours->getOpeningHours(), $hours, 1, 'day', false);
            $result = $this->calculateWorkingHourByInterval($result['existingDate'], $toDate, $openHours->getOpeningHours(), $result['hours'], 1, 'hours', false);
            $result = $this->calculateWorkingHourByInterval($result['existingDate'], $toDate, $openHours->getOpeningHours(), $result['hours'], 1, 'minutes', false);
        }

        return $result['hours'];
    }

    /**
     * @param \DateTime $fromDate
     * @param \DateTime $toDate
     * @param array     $openHours
     * @param int       $hours
     * @param int       $incrementBy
     * @param string    $interval
     * @param bool      $isWholeDay
     *
     * @return array
     */
    public function calculateWorkingHourByInterval(\DateTime $fromDate, \DateTime $toDate, array $openHours, int $hours, int $incrementBy, string $interval, bool $isWholeDay)
    {
        $modify = '+'.$incrementBy.' '.$interval;
        $currentDate = clone $fromDate;
        $fromDate = $fromDate->modify($modify);
        while ($fromDate <= $toDate) {
            $currentHourMin = \date('H:i', $currentDate->getTimestamp());
            if (\count($openHours) > 0) {
                $openHoursResult = $this->openingHoursSpecificationProcessor->processOpeningHour($currentDate, $openHours, null);
                if (null !== $openHoursResult['opens'] && null !== $openHoursResult['close']) {
                    $openHourMin = \date('H:i', $openHoursResult['opens']->getTimestamp());
                    $closeHourMin = \date('H:i', $openHoursResult['close']->getTimestamp());
                    if ($closeHourMin > $openHourMin) {
                        switch ($interval) {
                                    case 'day':
                                        if ($isWholeDay) {
                                            $hours += 1440;
                                        } else {
                                            $diff = \strtotime($closeHourMin) - \strtotime($openHourMin);
                                            $diff /= 3600;
                                            $hours = $hours + ($diff * 60);
                                        }
                                        break;
                                    case 'hours':
                                        if ($isWholeDay) {
                                            $hours += 60;
                                        } else {
                                            if (($openHourMin < $currentHourMin) && ($currentHourMin < $closeHourMin)) {
                                                $hours += 60;
                                            }
                                        }
                                        break;
                                    case 'minutes':
                                        if ($isWholeDay) {
                                            ++$hours;
                                        } else {
                                            if (($openHourMin < $currentHourMin) && ($currentHourMin < $closeHourMin)) {
                                                ++$hours;
                                            }
                                        }
                                        break;
                                }
                    }
                }
            }
            $currentDate = $currentDate->modify($modify);
            $fromDate = $fromDate->modify($modify);
        }

        return ['existingDate' => $currentDate, 'hours' => $hours];
    }

    // don't ask me why, logically makes sense
    public function getWorkingMinutes(\DateTime $date, array $openingHours, bool $today, ?\DateTime $endDate = null)
    {
        $calendar = null;
        $startDateTime = null;
        $timezone = $this->timezone;

        $startHour = '0';
        $startMinute = '0';
        $startSecond = '0';
        $endHour = '23';
        $endMinute = '59';
        $endSecond = '59';
        $minutes = 0;

        $qb = $this->entityManager->getRepository(CountryCalendar::class)->createQueryBuilder('calendar');

        if (false === $today) {
            $date->modify('+24 hours');
        }

        $calendars = $qb->select('calendar')
            ->where($qb->expr()->eq('calendar.enabled', ':enabled'))
            ->setParameters(['enabled' => true])
            ->orderBy('calendar.dateModified', 'DESC')
            ->getQuery()
            ->getResult();

        if (\count($calendars) > 0) {
            // get latest modified enabled calendar
            // probably needs more config tables if we want to support multiple timezones SLA
            $calendar = \reset($calendars);

            // use the calendar's timezone for calculation
            $timezoneList = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $calendar->getCountryCode());
            $timezone = new \DateTimeZone(\reset($timezoneList));
            $openingHours = \array_merge($openingHours, $calendar->getOpeningHours());
        }

        $openingHour = $this->openingHoursSpecificationProcessor->processOpeningHour($date, $openingHours, null);

        if (null !== $openingHour['opens']) {
            if (false === $today) {
                $startHour = $openingHour['opens']->format('H');
                $startMinute = $openingHour['opens']->format('i');
                $startSecond = $openingHour['opens']->format('s');
            }

            if (null !== $openingHour['close']) {
                $endHour = $openingHour['close']->format('H');
                $endMinute = $openingHour['close']->format('i');
                $endSecond = $openingHour['close']->format('s');
            }
        } else {
            // meaning not a working day
            return [
                $minutes,
                $date->getTimestamp(),
                $date->getTimestamp(),
            ];
        }

        // start datetime can be based on the calendar, or if no calendar the start will be the date supplied to the function itself
        $startDateTime = clone $date;
        $startDateTime->setTimezone($timezone);

        // specially for first time function calls, if today use current time, if not use the start of day
        if (false === $today) {
            $startDateTime->setTime((int) $startHour, (int) $startMinute, (int) $startSecond);
        }

        $endOfWorkingDay = clone $startDateTime;
        $endOfWorkingDay->setTime((int) $endHour, (int) $endMinute, (int) $endSecond);

        if (null !== $endDate && $endDate->getTimestamp() <= $endOfWorkingDay->getTimestamp()) {
            $endOfWorkingDay->setTimestamp($endDate->getTimestamp());
        }

        $diff = $endOfWorkingDay->getTimestamp() - $startDateTime->getTimestamp();

        if ($diff > 0) {
            $minutes = \round($diff / 60, 2);
        }

        return [
            $minutes,
            $startDateTime->getTimestamp(),
            $endOfWorkingDay->getTimestamp(),
        ];
    }

    public function isTimerPaused(Ticket $ticket)
    {
        if (TicketStatus::IN_PROGRESS !== $ticket->getStatus()->getValue()
            && TicketStatus::ASSIGNED !== $ticket->getStatus()->getValue()) {
            return true;
        }

        $now = new \DateTime();
        $openingHours = [];
        $timezone = $this->timezone;

        $qb = $this->entityManager->getRepository(CountryCalendar::class)->createQueryBuilder('calendar');

        $calendars = $qb->select('calendar')
            ->where($qb->expr()->eq('calendar.enabled', ':enabled'))
            ->setParameters(['enabled' => true])
            ->orderBy('calendar.dateModified', 'DESC')
            ->getQuery()
            ->getResult();

        if (null !== $ticket->getServiceLevelAgreement()) {
            $openingHours = $ticket->getServiceLevelAgreement()->getOperationExclusions();
        }

        if (\count($calendars) > 0) {
            // get latest modified enabled calendar
            // probably needs more config tables if we want to support multiple timezones SLA
            $calendar = \reset($calendars);

            // use the calendar's timezone for calculation
            $timezoneList = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $calendar->getCountryCode());
            $timezone = new \DateTimeZone(\reset($timezoneList));
            $openingHours = \array_merge($openingHours, $calendar->getOpeningHours());
        }

        if (\count($openingHours) > 0) {
            $openingHour = $this->openingHoursSpecificationProcessor->processOpeningHour($now, $openingHours, null);
            if (null !== $openingHour['opens'] && null !== $openingHour['close']) {
                $endHour = $openingHour['close']->format('H');
                $endMinute = $openingHour['close']->format('i');
                $endSecond = $openingHour['close']->format('s');

                $endOfWorkingDay = clone $now;
                $endOfWorkingDay->setTimezone($timezone);
                $endOfWorkingDay->setTime((int) $endHour, (int) $endMinute, (int) $endSecond);

                if ($endOfWorkingDay->getTimestamp() <= $now->getTimestamp()) {
                    return true;
                }
            } else {
                // no opening hours so it is not a working day
                return true;
            }
        }

        return false;
    }

    public function getWorkingMinutesFromDateRange(\DateTime $start, \DateTime $end, array $openingHours = [])
    {
        $result = 0;
        $startDay = true;

        do {
            list($workingMinutes, $startTimestamp, $endTimestamp) = $this->getWorkingMinutes($start, $openingHours, $startDay, $end);
            $startDay = false;
            $result += $workingMinutes;
        } while ($end->getTimestamp() > $endTimestamp);

        return $result;
    }
}
