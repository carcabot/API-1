<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 *  An enumeration of promotion code statuses.
 */
class PromotionStatus extends Enum
{
    /**
     * @var string Indicates that the promotion is active.
     */
    const ACTIVE = 'ACTIVE';

    /**
     * @var string Indicates that the promotion is inactive.
     */
    const INACTIVE = 'INACTIVE';

    /**
     * @var string Indicates that the promotion is new.
     */
    const NEW = 'NEW';
}
