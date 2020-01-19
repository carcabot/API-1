<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of item list type.
 */
class ItemListType extends Enum
{
    /**
     * @var string Indicates that the item list is campaign source.
     */
    const CAMPAIGN_SOURCE = 'CAMPAIGN_SOURCE';
}
