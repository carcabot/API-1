<?php

declare(strict_types=1);

namespace App\Bridge\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Bridge\Services\ContractApi;
use App\Entity\ApplicationRequest;
use App\Entity\User;
use App\Enum\ApplicationRequestStatus;
use Ds\Map;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ApplicationRequestUpdateSubscriber implements EventSubscriberInterface
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
     * @var Map<ApplicationRequest, array>
     */
    private $initialStatuses;

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
        $this->initialStatuses = new Map();
    }

    public static function getSubscribedEvents()
    {
        return [];

        return [
            KernelEvents::REQUEST => ['preUpdateApplicationRequest', EventPriorities::PRE_READ - 1],
            KernelEvents::VIEW => ['createApplicationRequest', EventPriorities::PRE_WRITE + 1],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function preUpdateApplicationRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $data = $request->attributes->get('data');

        if (!$data instanceof ApplicationRequest) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $data;

        $this->initialStatuses->put($applicationRequest, $applicationRequest->getStatus()->getValue());
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
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $controllerResult;

        if (null === $token) {
            return;
        }

        $initialStatus = $this->initialStatuses->get($applicationRequest, null);

        if (ApplicationRequestStatus::IN_PROGRESS !== $applicationRequest->getStatus()->getValue() ||
            ApplicationRequestStatus::IN_PROGRESS === $initialStatus
        ) {
            return;
        }

        $authenticatedUser = $token->getUser();

        if ($authenticatedUser instanceof User && null !== $bridgeUser = $authenticatedUser->getBridgeUser()) {
            $this->contractApi->createApplicationRequest($applicationRequest, $bridgeUser);
        }
    }
}
