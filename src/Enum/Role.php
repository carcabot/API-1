<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of roles.
 */
class Role extends Enum
{
    /**
     * @var string The contact person role.
     */
    const CONTACT_PERSON = 'CONTACT_PERSON';

    /**
     * @var string The sales representative role.
     */
    const SALES_REPRESENTATIVE = 'SALES_REPRESENTATIVE';
}
