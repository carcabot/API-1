<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of priorities.
 */
class Priority extends Enum
{
    /**
     * @var string Indicates high priority.
     */
    const HIGH = 'HIGH';

    /**
     * @var string Indicates low priority.
     */
    const LOW = 'LOW';

    /**
     * @var string Indicates medium priority.
     */
    const MEDIUM = 'MEDIUM';

    /**
     * @var string Indicates very high priority.
     */
    const VERY_HIGH = 'VERY_HIGH';
}
