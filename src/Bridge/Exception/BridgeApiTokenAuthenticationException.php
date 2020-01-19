<?php

declare(strict_types=1);

namespace App\Bridge\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class BridgeApiTokenAuthenticationException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'Invalid authentication token';
    }
}
