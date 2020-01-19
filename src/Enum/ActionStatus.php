<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of action statuses.
 */
class ActionStatus extends Enum
{
    /**
     * @var string Indicates that action is completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that action is failed.
     */
    const FAILED = 'FAILED';

    /**
     * @var string Indicates that action is in progress.
     */
    const IN_PROGRESS = 'IN_PROGRESS';
}
