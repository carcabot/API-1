<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * List of time identifiers.
 */
class TimeType extends Enum
{
    /**
     * @var string The day.
     */
    const DAY = 'DAY';

    /**
     * @var string The hour.
     */
    const HOUR = 'HUR';

    /**
     * @var string The minute.
     */
    const MIN = 'MIN';

    /**
     * @var string The month.
     */
    const MONTH = 'MON';

    /**
     * @var string The second.
     */
    const SEC = 'SEC';

    /**
     * @var string The year.
     */
    const YEAR = 'ANN';
}
