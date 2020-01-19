<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of sms types.
 */
class SMSType extends Enum
{
    /**
     * @var string Represents the customer service feedback sms type.
     */
    const CUSTOMER_SERVICE_FEEDBACK = 'CUSTOMER_SERVICE_FEEDBACK';
}
