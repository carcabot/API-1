<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of application request types.
 */
class ApplicationRequestType extends Enum
{
    /**
     * @var string Represents the account closure application request type.
     */
    const ACCOUNT_CLOSURE = 'ACCOUNT_CLOSURE';

    /**
     * @var string Represents the contract application request type.
     */
    const CONTRACT_APPLICATION = 'CONTRACT_APPLICATION';

    /**
     * @var string Represents the contract renewal request type.
     */
    const CONTRACT_RENEWAL = 'CONTRACT_RENEWAL';

    /**
     * @var string Represents the GIRO termination application request type.
     */
    const GIRO_TERMINATION = 'GIRO_TERMINATION';

    /**
     * @var string Represents the RCCS termination application request type.
     */
    const RCCS_TERMINATION = 'RCCS_TERMINATION';

    /**
     * @var string Represents the transfer out application request type.
     */
    const TRANSFER_OUT = 'TRANSFER_OUT';
}
