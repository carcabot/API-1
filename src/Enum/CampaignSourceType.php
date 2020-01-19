<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of campaign source types.
 */
class CampaignSourceType extends Enum
{
    /**
     * @var string Represents that campaign source is from customer account.
     */
    const CUSTOMER_ACCOUNT = 'CUSTOMER_ACCOUNT';

    /**
     * @var string Represents that campaign source is from external list.
     */
    const EXTERNAL_LIST = 'EXTERNAL_LIST';

    /**
     * @var string Represents that campaign source is from lead.
     */
    const LEAD = 'LEAD';
}
