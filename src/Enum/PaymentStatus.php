<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of payment statuses.
 */
class PaymentStatus extends Enum
{
    /**
     * @var string Indicates that the payment has been completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that the payment is failed.
     */
    const FAILED = 'FAILED';

    /**
     * @var string Indicates that the payment has been paid.
     */
    const PAID = 'PAID';

    /**
     * @var string Indicates that the payment is pending.
     */
    const PENDING = 'PENDING';
}
