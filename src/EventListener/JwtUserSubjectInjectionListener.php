<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class JwtUserSubjectInjectionListener
{
    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param AccessDecisionManagerInterface $decisionManager
     * @param TokenStorageInterface          $tokenStorage
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, TokenStorageInterface $tokenStorage)
    {
        $this->decisionManager = $decisionManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        $authenticatedUser = $token->getUser();
        if (!$authenticatedUser instanceof User) {
            return;
        }

        $payload = $event->getData();
        $payload['sub'] = (string) $authenticatedUser->getId();
        $payload['twoFactorAuthenticationStatus'] = null !== $authenticatedUser->getTwoFactorAuthenticationStatus()
            ? $authenticatedUser->getTwoFactorAuthenticationStatus() : null;

        if (null !== $authenticatedUser->getEmail() && empty($payload['username'])) {
            $payload['username'] = $authenticatedUser->getEmail();
        }

        $event->setData($payload);
    }
}
