<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserLoginHistory;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class JwtUserLastLogonUpdaterListener
{
    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param AccessDecisionManagerInterface $decisionManager
     * @param EntityManagerInterface         $entityManager
     * @param TokenStorageInterface          $tokenStorage
     * @param RequestStack                   $requestStack
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, RequestStack $requestStack)
    {
        $this->decisionManager = $decisionManager;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
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

        $now = new \DateTime();
        $authenticatedUser->setDateLastLogon($now);
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request) {
            $headers = $request->headers->all();
            $loginHistory = new UserLoginHistory();
            $loginHistory->setDate($now);
            if (isset($headers['x-real-ip'])) {
                $loginHistory->setIpAddress(\reset($headers['x-real-ip']));
            }

            if (isset($headers['user-agent'])) {
                $loginHistory->setDevice(\reset($headers['user-agent']));

                if (true !== $authenticatedUser->hasMobileDeviceLogin() &&
                    1 === \preg_match('/union|power|iswitch|i-switch|i switch|peerer|energy|ucentric|centric|okhttp/i', $loginHistory->getDevice())
                ) {
                    $authenticatedUser->setMobileDeviceLogin(true);
                }
            }
            $loginHistory->setUser($authenticatedUser);

            $this->entityManager->persist($loginHistory);
        }

        $this->entityManager->persist($authenticatedUser);
        $this->entityManager->flush();
    }
}
