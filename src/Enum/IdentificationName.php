<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of identification names.
 */
class IdentificationName extends Enum
{
    /**
     * @var string Represents the manufacturing industry.
     */
    const COMPANY_REGISTRATION_NUMBER = 'COMPANY_REGISTRATION_NUMBER';

    /**
     * @var string Represents the construction industry.
     */
    const MALAYSIA_IDENTITY_CARD = 'MALAYSIA_IDENTITY_CARD';

    /**
     * @var string Represents the utilities industry.
     */
    const NATIONAL_REGISTRATION_IDENTITY_CARD = 'NATIONAL_REGISTRATION_IDENTITY_CARD';

    /**
     * @var string Represents other industrial related industry.
     */
    const UNIQUE_ENTITY_NUMBER = 'UNIQUE_ENTITY_NUMBER';
}
