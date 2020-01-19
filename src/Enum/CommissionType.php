<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of commission types.
 */
class CommissionType extends Enum
{
    /**
     * @var string The fixed rate commission type.
     */
    const FIXED_RATE = 'FIXED_RATE';

    /**
     * @var string The percentage commission type.
     */
    const PERCENTAGE = 'PERCENTAGE';
}
