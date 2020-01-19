<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of campaign categories.
 */
class CampaignCategory extends Enum
{
    /**
     * @var string Represents that campaign type is direct mail.
     */
    const DIRECT_MAIL = 'DIRECT_MAIL';

    /**
     * @var string Represents that campaign type is e-mail.
     */
    const EMAIL = 'EMAIL';

    /**
     * @var string Represents that campaign type is SMS.
     */
    const SMS = 'SMS';
}
