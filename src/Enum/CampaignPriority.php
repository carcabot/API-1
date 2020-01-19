<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of campaign priorities.
 */
class CampaignPriority extends Enum
{
    /**
     * @var string Represents that campaign priority is high.
     */
    const HIGH = 'HIGH';

    /**
     * @var string Represents that campaign priority is medium.
     */
    const MEDIUM = 'MEDIUM';

    /**
     * @var string Represents that campaign priority is low.
     */
    const LOW = 'LOW';
}
