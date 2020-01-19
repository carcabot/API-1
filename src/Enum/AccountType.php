<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of account types.
 */
class AccountType extends Enum
{
    /**
     * @var string Indicates the corporate account type.
     */
    const CORPORATE = 'CORPORATE';

    /**
     * @var string Indicates the individual account type.
     */
    const INDIVIDUAL = 'INDIVIDUAL';
}
