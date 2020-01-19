<?php

declare(strict_types=1);

namespace App\Bridge\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Bridge\Services\ContractApi;
use App\Entity\ApplicationRequest;
use App\Entity\User;
use App\Enum\ApplicationRequestStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApplicationRequestCreateSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContractApi
     */
    private $contractApi;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param ContractApi                   $contractApi
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     */
    public function __construct(ContractApi $contractApi, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage)
    {
        $this->contractApi = $contractApi;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents()
    {
        return [];

        return [
            KernelEvents::VIEW => ['createApplicationRequest', EventPriorities::PRE_WRITE + 1],
        ];
    }

    public function createApplicationRequest(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        if (!$controllerResult instanceof ApplicationRequest) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $controllerResult;

        if (null === $token) {
            return;
        }

        if (ApplicationRequestStatus::IN_PROGRESS !== $applicationRequest->getStatus()->getValue()) {
            return;
        }

        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        $authenticatedUser = $token->getUser();
        $bridgeUser = null;

        if ($authenticatedUser instanceof User) {
            if (null !== $authenticatedUser->getBridgeUser()) {
                $bridgeUser = $authenticatedUser->getBridgeUser();
            } elseif (null !== $applicationRequest->getAcquiredFrom() && null !== $applicationRequest->getAcquiredFrom()->getUser() && null !== $applicationRequest->getAcquiredFrom()->getUser()->getBridgeUser()) {
                $bridgeUser = $applicationRequest->getAcquiredFrom()->getUser()->getBridgeUser();
            }

            if (null !== $bridgeUser) {
                $this->contractApi->createApplicationRequest($applicationRequest, $bridgeUser);
            }
        }
    }
}
