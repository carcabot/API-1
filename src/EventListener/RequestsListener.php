<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Security\Guard\Token\SecurePasswordToken;
use App\Security\TwoFactorAuthentication\TwoFactorAuthenticationHelper;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestsListener
{
    /**
     * @var TwoFactorAuthenticationHelper
     */
    private $helper;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param TwoFactorAuthenticationHelper $helper
     * @param TokenStorageInterface         $tokenStorage
     */
    public function __construct(TwoFactorAuthenticationHelper $helper, TokenStorageInterface $tokenStorage)
    {
        $this->helper = $helper;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onCoreRequest(GetResponseEvent $event)
    {
        $token = $this->tokenStorage->getToken();

        if (empty($token)) {
            return;
        }

        if ($token instanceof SecurePasswordToken) {
            return;
        }

        if (!$token instanceof JWTUserToken) {
            return;
        }

        $request = $event->getRequest();
        $user = null;
        if (null !== $this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
        }
        $response = new JsonResponse(['message' => 'Please supply the Authentication Code sent to your Email'], 400);
        $params = \json_decode($request->getContent(), true);

        if (!$user instanceof User) {
            return;
        }

        //Check if user has to do two-factor authentication
        if (empty($user->getTwoFactorAuthenticationCode())) {
            return;
        }

        if (!$user->hasTwoFactorAuthentication()) {
            return;
        }

        if ('POST' === $request->getMethod()) {
            if (\array_key_exists('token', $params)) {
                $response = $this->helper->verify($params['token']);

                $event->setResponse($response);
            }
        }

        $event->setResponse($response);
    }
}
