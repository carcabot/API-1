<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class BlacklistConfigurationAction extends Enum
{
    /**
     * @var string Indicates to check for blacklist customer while redeeming bill rebate.
     */
    const BILL_REBATE_REDEMPTION = 'BILL_REBATE_REDEMPTION';

    /**
     * @var string Indicates to check for blacklist contact person while submitting application request.
     */
    const CONTACT_PERSON_SUBMIT_APPLICATION = 'CONTACT_PERSON_SUBMIT_APPLICATION';

    /**
     * @var string Indicates to check for blacklisted customers while registering for SSP.
     */
    const CREATE_USER = 'CREATE_USER';

    /**
     * @var string Indicates to check for blacklist customer while submitting application request
     */
    const CUSTOMER_SUBMIT_APPLICATION = 'CUSTOMER_SUBMIT_APPLICATION';

    /**
     * @var string Indicates to check for blacklist customer while redemption.
     */
    const REDEMPTION = 'REDEMPTION';
}
