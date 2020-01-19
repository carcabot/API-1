<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class OfferType extends Enum
{
    /**
     * @var string Indicates that the offer type is bill rebate.
     */
    const BILL_REBATE = 'BILL_REBATE';

    /**
     * @var string Indicates that the offer type is voucher.
     */
    const VOUCHER = 'VOUCHER';
}
