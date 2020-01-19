<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Gedmo\Blameable\BlameableListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DoctrineExtensionsBlameableUserInjectionListener
{
    /**
     * @var BlameableListener
     */
    private $blameableListener;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param BlameableListener             $blameableListener
     * @param TokenStorageInterface         $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(BlameableListener $blameableListener, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->blameableListener = $blameableListener;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return;
        }

        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }
        $authenticatedUser = $token->getUser();

        if ($authenticatedUser instanceof User) {
            $this->blameableListener->setUserValue($authenticatedUser);
        }
    }
}
