<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class JobStatus extends Enum
{
    /**
     * @var string Indicates that job has been completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that job has failed.
     */
    const FAILED = 'FAILED';

    /**
     * @var string Indicates that job has been queued.
     */
    const QUEUED = 'QUEUED';

    /**
     * @var string Indicates that job has been scheduled.
     */
    const SCHEDULED = 'SCHEDULED';
}
