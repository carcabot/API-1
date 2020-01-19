<?php

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;

/**
 * A list of authorization roles.
 */
class AuthorizationRole extends Enum
{
    /**
     * @var string Indicates authorization role of an administrator.
     */
    const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * @var string Indicates authorization role of an api user.
     */
    const ROLE_API_USER = 'ROLE_API_USER';

    /**
     * @var string Indicates authorization role of the homepage.
     */
    const ROLE_HOMEPAGE = 'ROLE_HOMEPAGE';

    /**
     * @var string Indicates authorization role of a partner.
     */
    const ROLE_PARTNER = 'ROLE_PARTNER';

    /**
     * @var string Indicates authorization role of a public api.
     */
    const ROLE_PUBLIC_API = 'ROLE_PUBLIC_API';

    /**
     * @var string Indicates authorization role of a super administrator.
     */
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * @var string Indicates authorization role of a user.
     */
    const ROLE_USER = 'ROLE_USER';
}
