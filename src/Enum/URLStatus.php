<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of URL statuses.
 */
class URLStatus extends Enum
{
    /**
     * @var string Indicates that the URL is active.
     */
    const ACTIVE = 'ACTIVE';

    /**
     * @var string Indicates that the URL is inactive.
     */
    const INACTIVE = 'INACTIVE';
}
