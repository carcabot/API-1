<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * An enumeration of web page statuses.
 */
class WebPageStatus extends Enum
{
    /**
     * @var string Indicates that the web page is active.
     */
    const ACTIVE = 'ACTIVE';

    /**
     * @var string Indicates that the web page is completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that the web page is a draft.
     */
    const DRAFT = 'DRAFT';

    /**
     * @var string Indicates that the web page is in progress.
     */
    const IN_PROGRESS = 'IN_PROGRESS';

    /**
     * @var string Indicates that the web page is inactive.
     */
    const INACTIVE = 'INACTIVE';
}
