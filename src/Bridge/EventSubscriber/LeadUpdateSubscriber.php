<?php

declare(strict_types=1);

namespace App\Bridge\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Bridge\Services\LeadApi;
use App\Entity\Lead;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LeadUpdateSubscriber implements EventSubscriberInterface
{
    /**
     * @var LeadApi
     */
    private $bridgeLeadApi;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param LeadApi                       $bridgeLeadApi
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     */
    public function __construct(LeadApi $bridgeLeadApi, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage)
    {
        $this->bridgeLeadApi = $bridgeLeadApi;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents()
    {
        return [];

        return [
            KernelEvents::VIEW => ['createLead', EventPriorities::PRE_WRITE],
        ];
    }

    public function createLead(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        if (!($controllerResult instanceof Lead)) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var Lead $lead */
        $lead = $controllerResult;

        if (null === $token) {
            return;
        }

        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        if (null === $lead->getBridgeId()) {
            return;
        }

        $authenticatedUser = $token->getUser();

        // Only update if user has bridge credentials
        if ($authenticatedUser instanceof User && null !== $bridgeUser = $authenticatedUser->getBridgeUser()) {
            $this->bridgeLeadApi->updateLead($lead, $bridgeUser);
        }
    }
}
