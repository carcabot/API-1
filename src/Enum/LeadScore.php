<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of lead scores.
 */
class LeadScore extends Enum
{
    /**
     * @var string Indicates a lead score that is cold.
     */
    const COLD = 'COLD';

    /**
     * @var string Indicates a lead score that is hot.
     */
    const HOT = 'HOT';

    /**
     * @var string Indicates a lead score that is warm.
     */
    const WARM = 'WARM';
}
