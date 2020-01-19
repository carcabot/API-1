<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class ThirdPartyChargeCategory extends Enum
{
    /**
     * @var string
     */
    const CARBON_TAX = 'CARBON_TAX';

    /**
     * @var string
     */
    const MARKET_RELATED_CHARGES = 'MARKET_RELATED_CHARGES';

    /**
     * @var string
     */
    const MARKET_SUPPORT_RELATED_CHARGES = 'MARKET_SUPPORT_RELATED_CHARGES';

    /**
     * @var string
     */
    const TRANSMISSION_LICENCE_CHARGES = 'TRANSMISSION_LICENCE_CHARGES';
}
