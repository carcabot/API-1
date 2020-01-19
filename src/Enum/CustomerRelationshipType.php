<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of customer relationship types.
 */
class CustomerRelationshipType extends Enum
{
    /**
     * @var string The contact person relationship type.
     */
    const CONTACT_PERSON = 'CONTACT_PERSON';

    /**
     * @var string The partner contact person relationship type.
     */
    const PARTNER_CONTACT_PERSON = 'PARTNER_CONTACT_PERSON';

    /**
     * @var string The power of attorney relationship type.
     */
    const POWER_OF_ATTORNEY = 'POWER_OF_ATTORNEY';

    /**
     * @var string The tenant relationship type.
     */
    const TENANT = 'TENANT';
}
