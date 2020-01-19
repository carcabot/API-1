<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Disque\JobType;
use App\Entity\User;
use App\Enum\AuthorizationRole;
use App\Model\PasswordTokenGenerator;
use App\Service\AuthenticationHelper;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Ds\Map;
use League\Tactician\CommandBus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuthenticationHelper
     */
    private $authHelper;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var DisqueQueue
     */
    private $disqueQueue;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var PasswordTokenGenerator
     */
    private $passwordTokenGenerator;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Map<User, string|null>
     */
    private $plainPasswordValues;

    /**
     * @param AuthenticationHelper   $authHelper
     * @param CommandBus             $commandBus
     * @param DisqueQueue            $disqueQueue
     * @param IriConverterInterface  $iriConverter
     * @param PasswordTokenGenerator $passwordTokenGenerator
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(AuthenticationHelper $authHelper, CommandBus $commandBus, DisqueQueue $disqueQueue, IriConverterInterface $iriConverter, PasswordTokenGenerator $passwordTokenGenerator, TokenStorageInterface $tokenStorage)
    {
        $this->authHelper = $authHelper;
        $this->commandBus = $commandBus;
        $this->disqueQueue = $disqueQueue;
        $this->iriConverter = $iriConverter;
        $this->passwordTokenGenerator = $passwordTokenGenerator;
        $this->tokenStorage = $tokenStorage;
        $this->plainPasswordValues = new Map();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequestPostRead', EventPriorities::POST_READ - 1],
            ],
            KernelEvents::VIEW => [
                ['sendWelcomeEmail', EventPriorities::POST_WRITE - 1],
                ['sendActivationEmail', EventPriorities::POST_WRITE - 1],
            ],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequestPostRead(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $data = $request->attributes->get('data');

        if (!$data instanceof User) {
            return;
        }

        /** @var User $user */
        $user = $data;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        $this->plainPasswordValues->put($user, $user->getPlainPassword());
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function sendWelcomeEmail(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof User)) {
            return;
        }

        /** @var User $user */
        $user = $controllerResult;

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        if (false === $this->authHelper->hasRole(AuthorizationRole::ROLE_PARTNER) && false === $this->authHelper->hasRole(AuthorizationRole::ROLE_ADMIN)) {
            return;
        }

        $plainPassword = $this->plainPasswordValues->get($user, null);

        $job = new DisqueJob([
            'data' => [
                'user' => $this->iriConverter->getIriFromItem($user),
            ],
            'type' => JobType::USER_CREATED,
            'user' => [
                '@id' => $this->iriConverter->getIriFromItem($user),
                'username' => $user->getUsername() ?? $user->getEmail(),
                'password' => $plainPassword,
            ],
        ]);
        $this->disqueQueue->push($job);
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function sendActivationEmail(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof User)) {
            return;
        }

        /** @var User $user */
        $user = $controllerResult;

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        if (true === $this->authHelper->hasRole(AuthorizationRole::ROLE_PARTNER) || true === $this->authHelper->hasRole(AuthorizationRole::ROLE_ADMIN)) {
            return;
        }

        $passwordToken = $this->passwordTokenGenerator->generate($user);

        $job = new DisqueJob([
            'data' => [
                'user' => $this->iriConverter->getIriFromItem($user),
            ],
            'type' => JobType::USER_SIGNED_UP,
            'user' => [
                '@id' => $this->iriConverter->getIriFromItem($user),
                'token' => $passwordToken->getToken(),
            ],
        ]);
        $this->disqueQueue->push($job);
    }
}
