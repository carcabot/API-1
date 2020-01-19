<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of bill subscription types.
 */
class BillSubscriptionType extends Enum
{
    /**
     * @var string Represents the e-bill subscription type.
     */
    const ELECTRONIC = 'ELECTRONIC';

    /**
     * @var string Represents the hard copy subscription type.
     */
    const HARDCOPY = 'HARDCOPY';
}
