<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of commission statement data types.
 */
class CommissionStatementDataType extends Enum
{
    /**
     * @var string Represents the application request data type for a commission statement.
     */
    const APPLICATION_REQUEST = 'APPLICATION_REQUEST';

    /**
     * @var string Represents the lead data type for a commission statement.
     */
    const LEAD = 'LEAD';
}
