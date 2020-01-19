<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of commission categories.
 */
class CommissionCategory extends Enum
{
    /**
     * @var string The contract application commission category.
     */
    const CONTRACT_APPLICATION = 'CONTRACT_APPLICATION';

    /**
     * @var string The lead commission category.
     */
    const LEAD = 'LEAD';
}
