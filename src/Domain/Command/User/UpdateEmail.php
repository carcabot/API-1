<?php

declare(strict_types=1);

namespace App\Domain\Command\User;

use App\Entity\User;

/**
 * Updates email.
 */
class UpdateEmail
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var User
     */
    private $user;

    /**
     * @param User   $user
     * @param string $email
     */
    public function __construct(User $user, string $email)
    {
        $this->user = $user;
        $this->email = $email;
    }

    /**
     * Gets email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
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
