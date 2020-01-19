<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\CustomerAccount;
use App\Entity\User;
use App\Service\AuthenticationHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;

class AuthenticationHelperTest extends TestCase
{
    public function testGetAuthenticatedUser()
    {
        $userProphecy = $this->prophesize(User::class);
        $user = $userProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);
        $token = $tokenProphecy->reveal();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->getAuthenticatedUser();

        $this->assertEquals($user, $actualData);
    }

    public function testGetAuthenticatedUserWithoutToken()
    {
        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn(null);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->getAuthenticatedUser();

        $this->assertNull($actualData);
    }

    public function testGetAuthenticatedUserWithoutAccess()
    {
        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted('IS_AUTHENTICATED_FULLY')->willReturn(false);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->getAuthenticatedUser();

        $this->assertNull($actualData);
    }

    public function testGetAuthenticatedUserWithoutUser()
    {
        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn(null);
        $token = $tokenProphecy->reveal();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->getAuthenticatedUser();

        $this->assertNull($actualData);
    }

    public function testHasRole()
    {
        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $token = $tokenProphecy->reveal();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManagerProphecy->decide($token, ['TestRole'])->willReturn(true);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->hasRole('TestRole');

        $this->assertTrue($actualData);
    }

    public function testHasRoleWithoutToken()
    {
        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn(null);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->hasRole('TESTROLE');

        $this->assertFalse($actualData);
    }

    public function testHasRoleWithoutDecisionManager()
    {
        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $token = $tokenProphecy->reveal();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManagerProphecy->decide($token, ['TESTROLE'])->willReturn(false);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->hasRole('TESTROLE');

        $this->assertFalse($actualData);
    }

    public function testGetImpersonatorUser()
    {
        $userProphecy = $this->prophesize(User::class);
        $user = $userProphecy->reveal();

        $sourceTokenProphecy = $this->prophesize(TokenInterface::class);
        $sourceTokenProphecy->getUser()->willReturn($user);
        $sourceToken = $sourceTokenProphecy->reveal();

        $roleProphecy = $this->prophesize(SwitchUserRole::class);
        $roleProphecy->getSource()->willReturn($sourceToken);
        $role = $roleProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getRoles()->willReturn([$role]);
        $token = $tokenProphecy->reveal();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->getImpersonatorUser();

        $this->assertEquals($user, $actualData);
    }

    public function testGetImpersonatorUserWithoutToken()
    {
        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn(null);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->getImpersonatorUser();

        $this->assertNull($actualData);
    }

    public function testGetImpersonatorUserWithoutUser()
    {
        $sourceTokenProphecy = $this->prophesize(TokenInterface::class);
        $sourceTokenProphecy->getUser()->willReturn(null);
        $sourceToken = $sourceTokenProphecy->reveal();

        $roleProphecy = $this->prophesize(SwitchUserRole::class);
        $roleProphecy->getSource()->willReturn($sourceToken);
        $role = $roleProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getRoles()->willReturn([$role]);
        $token = $tokenProphecy->reveal();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->getImpersonatorUser();

        $this->assertNull($actualData);
    }

    public function testGetCustomerAccount()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccount = $customerAccountProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getCustomerAccount()->willReturn($customerAccount);
        $user = $userProphecy->reveal();

        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);
        $token = $tokenProphecy->reveal();

        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted('IS_AUTHENTICATED_FULLY')->willReturn(true);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorageProphecy->getToken()->willReturn($token);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->getCustomerAccount();

        $this->assertEquals($customerAccount, $actualData);
    }

    public function testGetCustomerAccountWithoutAuthenticatedUser()
    {
        $authorizationCheckerProphecy = $this->prophesize(AuthorizationCheckerInterface::class);
        $authorizationCheckerProphecy->isGranted('IS_AUTHENTICATED_FULLY')->willReturn(false);
        $authorizationChecker = $authorizationCheckerProphecy->reveal();

        $decisionManagerProphecy = $this->prophesize(AccessDecisionManagerInterface::class);
        $decisionManager = $decisionManagerProphecy->reveal();

        $tokenStorageProphecy = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage = $tokenStorageProphecy->reveal();

        $authenticationHelper = new AuthenticationHelper($authorizationChecker, $decisionManager, $tokenStorage);
        $actualData = $authenticationHelper->getCustomerAccount();

        $this->assertNull($actualData);
    }
}
