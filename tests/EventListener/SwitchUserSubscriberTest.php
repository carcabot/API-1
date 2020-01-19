<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\CustomerAccount;
use App\Entity\User;
use App\EventListener\SwitchUserSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserSubscriberTest extends TestCase
{
    public function testCheckSwitchAuthorisation()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn(['CUSTOMER']);
        $customerAccount = $customerAccountProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getRoles()->willReturn(['ROLE_HOMEPAGE']);
        $userProphecy->getCustomerAccount()->willReturn($customerAccount);
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

        $switchEventProphecy = $this->prophesize(SwitchUserEvent::class);
        $switchEventProphecy->getTargetUser()->willReturn($user);
        $switchEventProphecy->getToken()->willReturn($token);
        $switchEvent = $switchEventProphecy->reveal();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You have no power here.');

        $switchUserSubscriber = new SwitchUserSubscriber();
        $switchUserSubscriber->checkSwitchAuthorisation($switchEvent);
    }

    public function testCheckSwitchAuthorisationWithCategoryAsPartner()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn(['PARTNER']);
        $customerAccount = $customerAccountProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getRoles()->willReturn(['ROLE_HOMEPAGE']);
        $userProphecy->getCustomerAccount()->willReturn($customerAccount);
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

        $switchEventProphecy = $this->prophesize(SwitchUserEvent::class);
        $switchEventProphecy->getTargetUser()->willReturn($user);
        $switchEventProphecy->getToken()->willReturn($token);
        $switchEvent = $switchEventProphecy->reveal();

        $switchUserSubscriber = new SwitchUserSubscriber();
        $actualData = $switchUserSubscriber->checkSwitchAuthorisation($switchEvent);

        $this->assertNull($actualData);
    }
}
