<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of tariff rate types.
 */
class TariffRateType extends Enum
{
    /**
     * @var string Indicates that the tariff rate is of type dot offer.
     */
    const DOT_OFFER = 'DOT_OFFER';

    /**
     * @var string Indicates that the tariff rate is of type fixed rate.
     */
    const FIXED_RATE = 'FIXED_RATE';

    /**
     * @var string Indicates that the tariff rate is of type normal.
     */
    const NORMAL = 'NORMAL';

    /**
     * @var string Indicates that the tariff rate is of type pool price.
     */
    const POOL_PRICE = 'POOL_PRICE';
}
