<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of commission statement statuses.
 */
class CommissionStatementStatus extends Enum
{
    /**
     * @var string Indicates that the statement is completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that the statement is new.
     */
    const NEW = 'NEW';
}
