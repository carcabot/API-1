<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\PasswordToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PasswordTokenGenerator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Generates a PasswordToken.
     *
     * @param User        $user
     * @param string|null $tokenExpiration
     *
     * @return PasswordToken
     */
    public function generate(User $user, string $tokenExpiration = null): PasswordToken
    {
        $expiresAt = new \DateTime();

        if (null === $tokenExpiration) {
            $tokenExpiration = '+1 hour';
        }

        $expiresAt->modify($tokenExpiration);
        $token = \bin2hex(\random_bytes(25));

        $passwordToken = new PasswordToken();
        $passwordToken->setExpiresAt($expiresAt);
        $passwordToken->setToken($token);
        $passwordToken->setUser($user);

        $this->entityManager->persist($passwordToken);
        $this->entityManager->flush();

        return $passwordToken;
    }
}
