<?php

declare(strict_types=1);

namespace App\Domain\Command\User;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UpdatePasswordHandler
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function handle(UpdatePassword $command): void
    {
        $user = $command->getUser();
        $plainPassword = $command->getPlainPassword() ?? null;

        if (null === $plainPassword) {
            $user->setPassword(null);
        } else {
            $encodedPassword = $this->passwordEncoder->encodePassword($user, $plainPassword);

            $user->setPassword($encodedPassword);
        }
    }
}
