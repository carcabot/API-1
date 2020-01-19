<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of tariff rate statuses.
 */
class TariffRateStatus extends Enum
{
    /**
     * @var string Indicates that the tariff rate is active.
     */
    const ACTIVE = 'ACTIVE';

    /**
     * @var string Indicates that the tariff rate is completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that the tariff rate is deleted.
     */
    const DELETED = 'DELETED';

    /**
     * @var string Indicates that the tariff rate has ended.
     */
    const ENDED = 'ENDED';

    /**
     * @var string Indicates that the tariff rate is in progress.
     */
    const IN_PROGRESS = 'IN_PROGRESS';

    /**
     * @var string Indicates that the tariff rate is inactive.
     */
    const INACTIVE = 'INACTIVE';

    /**
     * @var string Indicates that the tariff rate is new.
     */
    const NEW = 'NEW';
}
