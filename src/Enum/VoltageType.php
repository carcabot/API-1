<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of voltage types.
 */
class VoltageType extends Enum
{
    /**
     * @var string Represents the extra high tension voltage type.
     */
    const EXTRA_HIGH_TENSION = 'EXTRA_HIGH_TENSION';

    /**
     * @var string Represents the high tension voltage type.
     */
    const HIGH_TENSION = 'HIGH_TENSION';

    /**
     * @var string Represents the low tension voltage type.
     */
    const LOW_TENSION = 'LOW_TENSION';
}
