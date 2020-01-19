<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class QuotationPricePlanType extends Enum
{
    /**
     * @var string Indicates that the price plan type is normal.
     */
    const NORMAL = 'NORMAL';

    /**
     * @var string Indicates that the price plan type is dot offer.
     */
    const DOT_OFFER = 'DOT_OFFER';

    /**
     * @var string Indicates that the price plan type is fixed rate.
     */
    const FIXED_RATE = 'FIXED_RATE';

    /**
     * @var string Indicates that the price plan type is pool price.
     */
    const POOL_PRICE = 'POOL_PRICE';
}
