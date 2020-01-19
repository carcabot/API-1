<?php

declare(strict_types=1);

namespace App\Domain\Command\User;

class AddRoleUserHandler
{
    public function handle(AddRoleUser $command): void
    {
        $user = $command->getUser();

        if (!\in_array('ROLE_USER', $user->getRoles(), true)) {
            $user->addRole('ROLE_USER');
        }
    }
}
