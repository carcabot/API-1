<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of ticket category types.
 */
class TicketCategoryType extends Enum
{
    /**
     * @var string The main category type.
     */
    const MAIN_CATEGORY = 'MAIN_CATEGORY';

    /**
     * @var string The sub category type.
     */
    const SUB_CATEGORY = 'SUB_CATEGORY';
}
