<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

class Permission extends Enum
{
    /**
     * @var string Indicates approve permission.
     */
    const APPROVE = 'APPROVE';

    /**
     * @var string Indicates assign permission.
     */
    const ASSIGN = 'ASSIGN';

    /**
     * @var string Indicates delete permission.
     */
    const DELETE = 'DELETE';

    /**
     * @var string Indicates read permission.
     */
    const READ = 'READ';

    /**
     * @var string Indicates write permission.
     */
    const WRITE = 'WRITE';
}
