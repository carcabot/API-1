<?php

declare(strict_types=1);

namespace App\Domain\Command\User;

use App\Entity\User;

/**
 * Adds the ROLE_USER to a user.
 */
class AddRoleUser
{
    /**
     * @var User
     */
    private $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Gets the user.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
