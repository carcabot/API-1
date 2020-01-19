<?php

declare(strict_types=1);

namespace App\Security\TwoFactorAuthentication;

use App\Entity\User;
use App\Security\Guard\Token\SecurePasswordToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginAttemptListener
{
    /**
     * @var TwoFactorAuthenticationHelper
     */
    private $helper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param TwoFactorAuthenticationHelper $helper
     * @param EntityManagerInterface        $em
     */
    public function __construct(TwoFactorAuthenticationHelper $helper, EntityManagerInterface $em)
    {
        $this->helper = $helper;
        $this->entityManager = $em;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if (!$event->getAuthenticationToken() instanceof TokenInterface) {
            return;
        }

        //Check if user can do two-factor authentication
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (!$user->hasTwoFactorAuthentication()) {
            return;
        }

        if ($token instanceof SecurePasswordToken) {
            return;
        }

        //Generate and send a new security code
        $this->helper->generateAndSend($user);
    }
}
