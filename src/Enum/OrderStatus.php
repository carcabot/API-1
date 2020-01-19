<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of order statuses.
 */
class OrderStatus extends Enum
{
    /**
     * @var string Indicates that the order is cancelled.
     */
    const CANCELLED = 'CANCELLED';

    /**
     * @var string Indicates that the order is delivered.
     */
    const DELIVERED = 'DELIVERED';

    /**
     * @var string Indicates that the order is a draft.
     */
    const DRAFT = 'DRAFT';

    /**
     * @var string Indicates that the order payment is due.
     */
    const PAYMENT_DUE = 'PAYMENT_DUE';

    /**
     * @var string Indicates that the order has a problem.
     */
    const PROBLEM = 'PROBLEM';

    /**
     * @var string Indicates that the order is processing.
     */
    const PROCESSING = 'PROCESSING';
}
