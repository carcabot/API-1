<?php

declare(strict_types=1);

namespace App\Model;

use App\Enum\DayOfWeek;

class OpeningHoursSpecificationProcessor
{
    /**
     * @param \DateTime $currentDate
     * @param array     $openHours
     * @param bool|null $paused
     *
     * @return array
     */
    public function processOpeningHour(\DateTime $currentDate, array $openHours, ?bool $paused)
    {
        $opens = null;
        $close = null;
        foreach ($openHours as $openHour) {
            if (DayOfWeek::PUBLIC_HOLIDAY === $openHour->getDayOfWeek()->getValue()) {
                if (null !== $openHour->getValidFrom() && null !== $openHour->getValidThrough()) {
                    if (($openHour->getValidFrom()->getTimestamp() <= $currentDate->getTimestamp())
                        && ($currentDate->getTimestamp() <= $openHour->getValidThrough()->getTimestamp())) {
                        $opens = null;
                        $close = null;
                        break;
                    }
                }
            }
            if ($openHour->getDayOfWeek()->getValue() === \strtoupper(\date('D', $currentDate->getTimestamp()))) {
                $opens = $openHour->getOpens();
                $close = $openHour->getCloses();
            }
        }

        return ['opens' => $opens, 'close' => $close, 'paused' => $paused];
    }
}
