<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of two factor authentication types.
 */
class TwoFactorAuthenticationType extends Enum
{
    /**
     * @var string Indicates the sms two factor authentication type.
     */
    const SMS = 'SMS';

    /**
     * @var string Indicates the email two factor authentication type.
     */
    const EMAIL = 'EMAIL';
}
