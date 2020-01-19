<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class QueueName extends Enum
{
    /**
     * @var string Indicates application request queue.
     */
    const APPLICATION_REQUEST = 'APPLICATION_REQUEST';

    /**
     * @var string Indicates campaign queue.
     */
    const CAMPAIGN = 'CAMPAIGN';

    /**
     * @var string Indicates contract queue.
     */
    const CONTRACT = 'CONTRACT';

    /**
     * @var string Indicates cron queue.
     */
    const CRON = 'CRON';

    /**
     * @var string Indicates email queue.
     */
    const EMAIL = 'EMAIL';

    /**
     * @var string Indicates message queue.
     */
    const MESSAGE = 'MESSAGE';

    /**
     * @var string Indicates report queue.
     */
    const REPORT = 'REPORT';

    /**
     * @var string Indicates web service queue.
     */
    const WEB_SERVICE = 'WEB_SERVICE';
}
