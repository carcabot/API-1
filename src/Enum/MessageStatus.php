<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of message statuses.
 */
class MessageStatus extends Enum
{
    /**
     * @var string Indicates that the message was canceled.
     */
    const CANCELED = 'CANCELED';

    /**
     * @var string Indicates that the message is completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that the message has ended.
     */
    const ENDED = 'ENDED';

    /**
     * @var string Indicates that the message is in progress.
     */
    const IN_PROGRESS = 'IN_PROGRESS';

    /**
     * @var string Indicates that the message is new.
     */
    const NEW = 'NEW';

    /**
     * @var string Indicates that the message has been scheduled.
     */
    const SCHEDULED = 'SCHEDULED';

    /**
     * @var string Indicates that the message is delivered.
     */
    const DELIVERED = 'DELIVERED';

    /**
     * @var string Indicates that the message has been sent.
     */
    const SENT = 'SENT';

    /**
     * @var string Indicates that the message bounced.
     */
    const BOUNCED = 'BOUNCED';

    /**
     * @var string Indicates that the message failed.
     */
    const FAILED = 'FAILED';

    /**
     * @var string Indicates that the message has been opened.
     */
    const OPENED = 'OPENED';

    /**
     * @var string Indicates that the message has been processed.
     */
    const PROCESSED = 'PROCESSED';
}
