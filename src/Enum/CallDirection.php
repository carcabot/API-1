<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of call directions.
 */
class CallDirection extends Enum
{
    /**
     * @var string Represents the inbound call direction.
     */
    const INBOUND = 'INBOUND';

    /**
     * @var string Represents the outbound call direction.
     */
    const OUTBOUND = 'OUTBOUND';
}
