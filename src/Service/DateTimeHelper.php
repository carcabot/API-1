<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\TimeType;

class DateTimeHelper
{
    public function dateDiff($interval, $start, $end, $relative = false)
    {
        if (\is_string($start)) {
            $start = \date_create($start);
        }
        if (\is_string($end)) {
            $end = \date_create($end);
        }

        $diff = \date_diff($start, $end, !$relative);
        $total = 0;

        switch ($interval) {
            case 'y':
                $total = $diff->y + $diff->m / 12 + $diff->d / 365.25; break;
            case 'm':
                $total = $diff->y * 12 + $diff->m + $diff->d / 30 + $diff->h / 24;
                break;
            case 'd':
                $total = $diff->y * 365.25 + $diff->m * 30 + $diff->d + $diff->h / 24 + $diff->i / 60;
                break;
            case 'h':
                $total = ($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h + $diff->i / 60;
                break;
            case 'i':
                $total = (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i + $diff->s / 60;
                break;
            case 's':
                $total = ((($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i) * 60 + $diff->s;
                break;
        }
        if ($diff->invert) {
            return -1 * $total;
        }

        return $total;
    }

    public function dateModify($interval)
    {
        $modify = null;

        switch ($interval) {
            case TimeType::DAY:
                $modify = 'day';
                break;
            case TimeType::HOUR:
                $modify = 'hours';
                break;
            case TimeType::MIN:
                $modify = 'minutes';
                break;
            case TimeType::SEC:
                $modify = 'seconds';
                break;
        }

        return $modify;
    }
}
