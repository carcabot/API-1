<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of genders.
 *
 * @see http://schema.org/GenderType
 */
class GenderType extends Enum
{
    /**
     * @var string The female gender.
     */
    const FEMALE = 'FEMALE';

    /**
     * @var string The male gender.
     */
    const MALE = 'MALE';
}
