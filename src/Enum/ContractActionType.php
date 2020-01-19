<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of contract action types.
 */
class ContractActionType extends Enum
{
    /**
     * @var string Represents the action for a contract account closure.
     */
    const ACCOUNT_CLOSURE = 'ACCOUNT_CLOSURE';

    /**
     * @var string Represents the action for a contract renewal.
     */
    const CONTRACT_RENEWAL = 'CONTRACT_RENEWAL';

    /**
     * @var string Represents the action for a contract GIRO termination.
     */
    const GIRO_TERMINATION = 'GIRO_TERMINATION';

    /**
     * @var string Represents the action for a contract transfer out.
     */
    const TRANSFER_OUT = 'TRANSFER_OUT';
}
