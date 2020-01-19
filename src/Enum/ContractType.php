<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of contract types.
 */
class ContractType extends Enum
{
    /**
     * @var string Indicates contract type of a commercial contract.
     */
    const COMMERCIAL = 'COMMERCIAL';

    /**
     * @var string Indicates contract type of a residential contract.
     */
    const RESIDENTIAL = 'RESIDENTIAL';
}
