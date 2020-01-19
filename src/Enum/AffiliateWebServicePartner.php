<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of affiliate web service partners.
 */
class AffiliateWebServicePartner extends Enum
{
    /**
     * @var string The affiliate gateway.
     *
     * @ref https://involve.asia/
     */
    const INVOLVE_ASIA = 'INVOLVE_ASIA';

    /**
     * @var string The lazada affiliate program.
     *
     * @ref https://lazada.hasoffers.com/
     */
    const LAZADA_HASOFFERS = 'LAZADA_HASOFFERS';

    /**
     * @var string Involve Asia.
     *
     * @ref https://www.tagadmin.sg
     */
    const TAG = 'TAG';
}
