<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class DayOfWeek extends Enum
{
    /**
     * @var string
     */
    const MON = 'MON';
    /**
     * @var string
     */
    const TUE = 'TUE';
    /**
     * @var string
     */
    const WED = 'WED';
    /**
     * @var string
     */
    const THU = 'THU';
    /**
     * @var string
     */
    const FRI = 'FRI';
    /**
     * @var string
     */
    const SAT = 'SAT';
    /**
     * @var string
     */
    const SUN = 'SUN';
    /**
     * @var string
     */
    const PUBLIC_HOLIDAY = 'PUBLIC_HOLIDAY';
}
