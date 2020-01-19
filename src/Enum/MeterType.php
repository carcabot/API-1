<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of meter types.
 */
class MeterType extends Enum
{
    /**
     * @var string Represents the AMI meter type.
     */
    const AMI = 'AMI';

    /**
     * @var string Represents the SRLP meter type.
     */
    const SRLP = 'SRLP';
}
