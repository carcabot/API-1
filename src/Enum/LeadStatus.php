<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of lead statuses.
 */
class LeadStatus extends Enum
{
    /**
     * @var string Indicates that the lead is assigned.
     */
    const ASSIGNED = 'ASSIGNED';

    /**
     * @var string Indicates that the lead is cancelled.
     */
    const CANCELLED = 'CANCELLED';

    /**
     * @var string Indicates that the lead is contacted.
     */
    const CONTACTED = 'CONTACTED';

    /**
     * @var string Indicates that the lead is converted to a customer.
     */
    const CONVERTED = 'CONVERTED';

    /**
     * @var string Indicates that the lead is disqualified.
     */
    const DISQUALIFIED = 'DISQUALIFIED';

    /**
     * @var string Indicates that the lead is a draft.
     */
    const DRAFT = 'DRAFT';

    /**
     * @var string Indicates that the lead is new.
     */
    const NEW = 'NEW';

    /**
     * @var string Indicates that the lead is a draft in partnership portal.
     */
    const PARTNER_DRAFT = 'PARTNER_DRAFT';

    /**
     * @var string Indicates that the lead is pending approval.
     */
    const PENDING = 'PENDING';

    /**
     * @var string Indicates that the lead has a quotation submitted.
     */
    const QUOTATION_SUBMITTED = 'QUOTATION_SUBMITTED';

    /**
     * @var string Indicates that the lead is rejected.
     */
    const REJECTED = 'REJECTED';
}
