<?php
/*
 * This file is part of the U-Centric project.
 *
 * (c) U-Centric Development Team <dev@ucentric.sisgroup.sg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of quotation statuses.
 */
class QuotationStatus extends Enum
{
    /**
     * @var string Indicates that the quotation is completed.
     */
    const COMPLETED = 'COMPLETED';

    /**
     * @var string Indicates that the quotation is cancelled.
     */
    const CANCELLED = 'CANCELLED';

    /**
     * @var string Indicates that the quotation is approved.
     */
    const APPROVED = 'APPROVED';

    /**
     * @var string Indicates that the quotation is rejected.
     */
    const REJECTED = 'REJECTED';

    /**
     * @var string Indicates that the quotation is a draft.
     */
    const DRAFT = 'DRAFT';

    /**
     * @var string Indicates that the quotation is sent.
     */
    const SENT = 'SENT';

    /**
     * @var string Indicates that the quotation is pending approval.
     */
    const PENDING = 'PENDING';

    /**
     * @var string Indicates that the quotation won.
     */
    const WON = 'WON';

    /**
     * @var string Indicates that the quotation is lost.
     */
    const LOST = 'LOST';
}
