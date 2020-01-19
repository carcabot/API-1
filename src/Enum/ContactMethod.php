<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of contact methods.
 */
class ContactMethod extends Enum
{
    /**
     * @var string The email contact method.
     */
    const EMAIL = 'EMAIL';

    /**
     * @var string The email and phone contact method.
     */
    const EMAIL_AND_PHONE = 'EMAIL_AND_PHONE';

    /**
     * @var string The phone contact method.
     */
    const PHONE = 'PHONE';
}
