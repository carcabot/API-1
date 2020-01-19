<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of SMS web service partners.
 */
class SMSWebServicePartner extends Enum
{
    /**
     * @var string Dummy.
     */
    const DUMMY = 'DUMMY';

    /**
     * @var string FortDigital.
     *
     * @ref http://www.fortdigital.com.sg/
     */
    const FORT_DIGITAL = 'FORT_DIGITAL';
}
