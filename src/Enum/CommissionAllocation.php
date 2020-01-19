<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of commission allocation types.
 */
class CommissionAllocation extends Enum
{
    /**
     * @var string The allocation type for money credits.
     */
    const MONEY = 'MONEY';

    /**
     * @var string The allocation type for point credits.
     */
    const POINTS = 'POINTS';
}
