<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of campaign status.
 */
class CampaignStatus extends Enum
{
    /**
     * @var string Represents the cancelled campaign status.
     */
    const CANCELLED = 'CANCELLED';

    /**
     * @var string Represents the ended campaign status.
     */
    const ENDED = 'ENDED';

    /**
     * @var string Represents the executed campaign status.
     */
    const EXECUTED = 'EXECUTED';

    /**
     * @var string Represents the new campaign status.
     */
    const NEW = 'NEW';

    /**
     * @var string Represents the scheduled campaign status.
     */
    const SCHEDULED = 'SCHEDULED';
}
