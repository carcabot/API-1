<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of sms directions.
 */
class SMSDirection extends Enum
{
    /**
     * @var string Represents the inbound sms direction.
     */
    const INBOUND = 'INBOUND';

    /**
     * @var string Represents the outbound sms direction.
     */
    const OUTBOUND = 'OUTBOUND';
}
