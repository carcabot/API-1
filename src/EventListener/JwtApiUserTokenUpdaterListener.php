<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class JwtApiUserTokenUpdaterListener
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

        if ($this->decisionManager->decide($token, ['ROLE_API_USER'])) {
            $expiration = new \DateTime('+10 years');
            $payload['exp'] = $expiration->getTimestamp();
        }

        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            $expiration = new \DateTime('+10 years');
            $payload['exp'] = $expiration->getTimestamp();
        }

        if ($this->decisionManager->decide($token, ['ROLE_HOMEPAGE'])) {
            $expiration = new \DateTime('+10 years');
            $payload['exp'] = $expiration->getTimestamp();
        }

        $event->setData($payload);
    }
}
