<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of marital statuses.
 */
class MaritalStatus extends Enum
{
    /**
     * @var string Indicates that the status is divorced.
     */
    const DIVORCED = 'DIVORCED';

    /**
     * @var string Indicates that the status is married.
     */
    const MARRIED = 'MARRIED';

    /**
     * @var string Indicates that the status is not reported.
     */
    const NOT_REPORTED = 'NOT_REPORTED';

    /**
     * @var string Indicates that the status is separated.
     */
    const SEPARATED = 'SEPARATED';

    /**
     * @var string Indicates that the status is single.
     */
    const SINGLE = 'SINGLE';

    /**
     * @var string Indicates that the status is widowed.
     */
    const WIDOWED = 'WIDOWED';
}
