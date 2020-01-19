<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of application request statuses.
 */
class ApplicationRequestStatus extends Enum
{
    /**
     * @var string Indicates that the application request is cancelled.
     */
    const CANCELLED = 'CANCELLED';

    /**
     * @var string Indicates that the application request is completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that the application request is a draft.
     */
    const DRAFT = 'DRAFT';

    /**
     * @var string Indicates that the application request authorization url is expired.
     */
    const AUTHORIZATION_URL_EXPIRED = 'AUTHORIZATION_URL_EXPIRED';
    /**
     * @var string Indicates that the application request is in progress.
     */
    const IN_PROGRESS = 'IN_PROGRESS';

    /**
     * @var string Indicates that the application request is a draft in partnership portal.
     */
    const PARTNER_DRAFT = 'PARTNER_DRAFT';

    /**
     * @var string Indicates that the application request is pending approval.
     */
    const PENDING = 'PENDING';

    /**
     * @var string Indicates that the application request is pending billing status update.
     */
    const PENDING_BILLING_STATUS = 'PENDING_BILLING_STATUS';

    /**
     * @var string Indicates that the application request is rejected.
     */
    const REJECTED = 'REJECTED';

    /**
     * @var string Indicates that the application request is rejected by owner.
     */
    const REJECTED_BY_OWNER = 'REJECTED_BY_OWNER';

    /**
     * @var string Indicates that the application request is voided.
     */
    const VOIDED = 'VOIDED';
}
