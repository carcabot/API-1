<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of contract statuses.
 */
class ContractStatus extends Enum
{
    /**
     * @var string Indicates that the contract is active.
     */
    const ACTIVE = 'ACTIVE';

    /**
     * @var string Indicates that the contract is cancelled.
     */
    const CANCELLED = 'CANCELLED';

    /**
     * @var string Indicates that the contract is a draft.
     */
    const DRAFT = 'DRAFT';

    /**
     * @var string Indicates that the contract is inactive.
     */
    const INACTIVE = 'INACTIVE';

    /**
     * @var string Indicates that the contract is new.
     */
    const NEW = 'NEW';

    /**
     * @var string Indicates that the contract is pending approval.
     */
    const PENDING = 'PENDING';

    /**
     * @var string Indicates that the contract is rejected.
     */
    const REJECTED = 'REJECTED';
}
