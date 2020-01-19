<?php

declare(strict_types=1);

namespace App\Bridge\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Bridge\Services\UserApi;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserUpdateSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserApi
     */
    private $bridgeUserApi;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param UserApi                       $bridgeUserApi
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     */
    public function __construct(UserApi $bridgeUserApi, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage)
    {
        $this->bridgeUserApi = $bridgeUserApi;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents()
    {
        return [];

        return [
            KernelEvents::VIEW => ['updateUser', EventPriorities::PRE_WRITE],
        ];
    }

    public function updateUser(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        if (!$controllerResult instanceof User) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var User $user */
        $user = $controllerResult;

        if (null === $user->getBridgeUser()) {
            return;
        }

        if (null === $token) {
            return;
        }

        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        $authenticatedUser = $token->getUser();

        if ($authenticatedUser instanceof User && null !== $bridgeUser = $authenticatedUser->getBridgeUser()) {
            $this->bridgeUserApi->updateUser($user, $bridgeUser);
        }
    }
}
