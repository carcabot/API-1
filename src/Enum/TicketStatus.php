<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of ticket statuses.
 */
class TicketStatus extends Enum
{
    /**
     * @var string The assigned status
     */
    const ASSIGNED = 'ASSIGNED';

    /**
     * @var string The cancelled status.
     */
    const CANCELLED = 'CANCELLED';

    /**
     * @var string The completed status.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string The in progress status.
     */
    const IN_PROGRESS = 'IN_PROGRESS';

    /**
     * @var string The new status.
     */
    const NEW = 'NEW';

    /**
     * @var string The pending billing team status.
     */
    const PENDING_BILLING_TEAM = 'PENDING_BILLING_TEAM';

    /**
     * @var string The pending customer action status.
     */
    const PENDING_CUSTOMER_ACTION = 'PENDING_CUSTOMER_ACTION';
}
