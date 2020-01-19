<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of account categories.
 */
class AccountCategory extends Enum
{
    /**
     * @var string Indicates the contact person account category.
     */
    const CONTACT_PERSON = 'CONTACT_PERSON';

    /**
     * @var string Indicates the customer account category.
     */
    const CUSTOMER = 'CUSTOMER';

    /**
     * @var string Indicates the employee account category.
     */
    const EMPLOYEE = 'EMPLOYEE';

    /**
     * @var string Indicates the noncustomer account category.
     */
    const NONCUSTOMER = 'NONCUSTOMER';

    /**
     * @var string Indicates the partner account category.
     */
    const PARTNER = 'PARTNER';

    /**
     * @var string Indicates the partner contact person account category.
     */
    const PARTNER_CONTACT_PERSON = 'PARTNER_CONTACT_PERSON';

    /**
     * @var string Indicates the sales representative account category.
     */
    const SALES_REPRESENTATIVE = 'SALES_REPRESENTATIVE';
}
