<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of catalog statuses.
 */
class CatalogStatus extends Enum
{
    /**
     * @var string Indicates that the catalog is active.
     */
    const ACTIVE = 'ACTIVE';

    /**
     * @var string Indicates that the catalog is a draft.
     */
    const DRAFT = 'DRAFT';

    /**
     * @var string Indicates that the catalog is inactive.
     */
    const INACTIVE = 'INACTIVE';
}
