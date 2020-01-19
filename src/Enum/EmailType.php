<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class EmailType extends Enum
{
    /**
     * @var string Represents the application request authorization notification type.
     */
    const APPLICATION_REQUEST_AUTHORIZATION_NOTIFICATION = 'APPLICATION_REQUEST_AUTHORIZATION_NOTIFICATION';

    /**
     * @var string Represents the application request authorization reminder type.
     */
    const APPLICATION_REQUEST_AUTHORIZATION_REMINDER = 'APPLICATION_REQUEST_AUTHORIZATION_REMINDER';
}
