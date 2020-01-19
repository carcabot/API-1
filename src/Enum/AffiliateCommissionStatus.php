<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of affiliate commission statuses.
 */
class AffiliateCommissionStatus extends Enum
{
    /**
     * @var string Indicates that the commission is approved.
     */
    const APPROVED = 'APPROVED';

    /**
     * @var string Indicates that the commission is declined.
     */
    const DECLINED = 'DECLINED';

    /**
     * @var string Indicates that the commission is pending.
     */
    const PENDING = 'PENDING';
}
