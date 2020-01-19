<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class PaymentMode extends Enum
{
    /**
     * @var string Indicates that the payment mode is manual.
     */
    const MANUAL = 'MANUAL';

    /**
     * @var string Indicates that the payment mode is GIRO.
     */
    const GIRO = 'GIRO';

    /**
     * @var string Indicates that the payment mode is RCCS.
     */
    const RCCS = 'RCCS';
}
