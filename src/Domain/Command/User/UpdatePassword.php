<?php

declare(strict_types=1);

namespace App\Domain\Command\User;

use App\Entity\User;

/**
 * Updates password.
 */
class UpdatePassword
{
    /**
     * @var string|null
     */
    private $plainPassword;

    /**
     * @var User
     */
    private $user;

    /**
     * @param User        $user
     * @param string|null $plainPassword
     */
    public function __construct(User $user, ?string $plainPassword)
    {
        $this->user = $user;
        $this->plainPassword = $plainPassword;
    }

    /**
     * Gets plainPassword.
     *
     * @return string
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
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
