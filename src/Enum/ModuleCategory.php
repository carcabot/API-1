<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class ModuleCategory extends Enum
{
    /**
     * @var string Indicates the module belongs to CRM.
     */
    const CRM = 'CRM';
    /**
     * @var string Indicates the module belongs to HOMEPAGE.
     */
    const HOMEPAGE = 'HOMEPAGE';
    /**
     * @var string Indicates the module belongs to SSP.
     */
    const SSP = 'SSP';
}
