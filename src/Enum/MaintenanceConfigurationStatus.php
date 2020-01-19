<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of maintenance configuration statuses.
 */
class MaintenanceConfigurationStatus extends Enum
{
    /**
     * @var string Indicates that the maintenance is active.
     */
    const ACTIVE = 'ACTIVE';

    /**
     * @var string Indicates that the maintenance was cancelled.
     */
    const CANCELLED = 'CANCELLED';

    /**
     * @var string Indicates that the maintenance is completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that the maintenance is pending.
     */
    const PENDING = 'PENDING';
}
