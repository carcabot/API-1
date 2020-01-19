<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of unsubscribe reason status.
 */
class UnsubscribeReasonStatus extends Enum
{
    /**
     * @var string Represents that unsubscribe reason is active.
     */
    const ACTIVE = 'ACTIVE';

    /**
     * @var string Represents that unsubscribe reason is inactive.
     */
    const INACTIVE = 'INACTIVE';
}
