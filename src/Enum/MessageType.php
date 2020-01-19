<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of account types.
 */
class MessageType extends Enum
{
    /**
     * @var string Represents the email message type.
     */
    const EMAIL = 'EMAIL';

    /**
     * @var string Represents the customer service push notification message type.
     */
    const PUSH_NOTIFICATION = 'PUSH_NOTIFICATION';

    /**
     * @var string Represents the customer service sms message type.
     */
    const SMS = 'SMS';
}
