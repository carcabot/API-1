<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of refund types.
 */
class RefundType extends Enum
{
    /**
     * @var string Indicates a bill offset refund type.
     */
    const BILL_OFFSET = 'BILL_OFFSET';

    /**
     * @var string Indicates a full cash refund type.
     */
    const FULL_REFUND = 'FULL_REFUND';
}
