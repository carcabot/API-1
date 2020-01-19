<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CustomerAccount;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;

/*
 * As the name says, it helps.
 */
class AuthenticationHelper
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param AuthorizationCheckerInterface  $authorizationChecker
     * @param AccessDecisionManagerInterface $decisionManager
     * @param TokenStorageInterface          $tokenStorage
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, AccessDecisionManagerInterface $decisionManager, TokenStorageInterface $tokenStorage)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->decisionManager = $decisionManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Gets logged in User.
     *
     * @return User|null
     */
    public function getAuthenticatedUser(): ?User
    {
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return null;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return null;
        }

        $authenticatedUser = $token->getUser();

        if (!$authenticatedUser instanceof User) {
            return null;
        }

        return $authenticatedUser;
    }

    /**
     * Gets logged in user's CustomerAccount.
     *
     * @return CustomerAccount|null
     */
    public function getCustomerAccount(): ?CustomerAccount
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        if (null !== $authenticatedUser) {
            return $authenticatedUser->getCustomerAccount();
        }

        return null;
    }

    /**
     * Check logged in user role.
     *
     * @param string $role
     *
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return false;
        }

        if (!$this->decisionManager->decide($token, [$role])) {
            return false;
        }

        return true;
    }

    /**
     * Returns impersonator user if there is an impersonation going on.
     *
     * @return User|null
     */
    public function getImpersonatorUser(): ?User
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return null;
        }

        $impersonatorUser = null;

        foreach ($token->getRoles() as $role) {
            if ($role instanceof SwitchUserRole) {
                $impersonatorUser = $role->getSource()->getUser();
                break;
            }
        }

        if ($impersonatorUser instanceof User) {
            return $impersonatorUser;
        }

        return null;
    }
}
