<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of customer account statuses.
 */
class CustomerAccountStatus extends Enum
{
    /**
     * @var string Indicates that the customer account is active.
     */
    const ACTIVE = 'ACTIVE';

    /**
     * @var string Indicates that the customer account is inactive.
     */
    const INACTIVE = 'INACTIVE';
}
