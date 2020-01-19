<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of account types.
 */
class ImportListingTargetFields extends Enum
{
    /**
     * @var string Indicates to use the identifiers fields.
     */
    const IDENTIFICATION = 'IDENTIFICATION';

    /**
     * @var string Indicates to use the email fields.
     */
    const EMAIL = 'EMAIL';

    /**
     * @var string Indicates to use the phone number fields.
     */
    const PHONE = 'PHONE';

    /**
     * @var string Indicates to use the mobile phone number fields.
     */
    const MOBILE = 'MOBILE';
}
