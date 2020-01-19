<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of credits type.
 */
class CreditsType extends Enum
{
    /**
     * @var string Indicates credits type of one time points (credits earned only once from an action).
     */
    const OP = 'OP';

    /**
     * @var string Indicates credits type of recurring points (credits earned multiple times from the same action).
     */
    const RP = 'RP';
}
