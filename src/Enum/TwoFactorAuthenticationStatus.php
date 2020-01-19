<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of two factor authentication types.
 */
class TwoFactorAuthenticationStatus extends Enum
{
    /**
     * @var string Indicates the two factor authentication is still pending.
     */
    const PENDING = 'PENDING';

    /**
     * @var string Indicates the two factor authentication is complete.
     */
    const COMPLETE = 'COMPLETE';
}
