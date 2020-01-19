<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of postal address types.
 */
class PostalAddressType extends Enum
{
    /**
     * @var string Indicates that the address is a correspondence address.
     */
    const CORRESPONDENCE_ADDRESS = 'CORRESPONDENCE_ADDRESS';

    /**
     * @var string Indicates that the address is a mailing address.
     */
    const MAILING_ADDRESS = 'MAILING_ADDRESS';

    /**
     * @var string Indicates that the address is a premise address.
     */
    const PREMISE_ADDRESS = 'PREMISE_ADDRESS';

    /**
     * @var string Indicates that the address is a refund address.
     */
    const REFUND_ADDRESS = 'REFUND_ADDRESS';

    /**
     * @var string Indicates that the address is a secondary bill address.
     */
    const SECONDARY_BILL_ADDRESS = 'SECONDARY_BILL_ADDRESS';
}
