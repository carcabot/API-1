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

class ApplicationRequestValidateSubscriber implements EventSubscriberInterface
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
            KernelEvents::VIEW => ['validateApplicationRequest', EventPriorities::POST_VALIDATE],
        ];
    }

    public function validateApplicationRequest(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        if (!$controllerResult instanceof ApplicationRequest) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $controllerResult;

        if (null === $token) {
            return;
        }

        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        if (null === $applicationRequest->getBridgeId()) {
            return;
        }

        if (ApplicationRequestStatus::IN_PROGRESS !== $applicationRequest->getStatus()->getValue()) {
            return;
        }

        $authenticatedUser = $token->getUser();

        if ($authenticatedUser instanceof User && null !== $bridgeUser = $authenticatedUser->getBridgeUser()) {
            $this->contractApi->validateApplicationRequest($applicationRequest, $bridgeUser);
        }
    }
}
