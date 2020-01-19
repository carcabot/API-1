<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\CustomerAccount\UpdateCategories;
use App\Domain\Command\CustomerAccount\UpdateRelationships;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountRelationship;
use App\Enum\CustomerRelationshipType;
use App\EventListener\CustomerAccountRelationshipsUpdaterListener;
use App\Model\CustomerAccountPortalEnableUpdater;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class CustomerAccountRelationshipsUpdaterListenerTest extends TestCase
{
    public function testOnKernelView()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $fromAccountProphecy = $this->prophesize(CustomerAccount::class);
        $fromAccount = $fromAccountProphecy->reveal();

        $customerAccountRelationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $customerAccountRelationshipProphecy->getFrom()->willReturn($fromAccount);
        $customerAccountRelationshipProphecy->getType()->willReturn(new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON));
        $customerAccountRelationship = $customerAccountRelationshipProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerAccountRelationship);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateRelationships($customerAccountRelationship))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateCategories($customerAccountRelationship))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $customerAccountPortalEnableUpdaterProphecy = $this->prophesize(CustomerAccountPortalEnableUpdater::class);
        $customerAccountPortalEnableUpdater = $customerAccountPortalEnableUpdaterProphecy->reveal();

        $customerAccountRelationshipUpdaterListener = new CustomerAccountRelationshipsUpdaterListener($commandBus, $customerAccountPortalEnableUpdater);
        $customerAccountRelationshipUpdaterListener->onKernelView($event);
    }

    public function testOnKernelViewWithoutCustomerAccountRelationship()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $customerAccountPortalEnableUpdaterProphecy = $this->prophesize(CustomerAccountPortalEnableUpdater::class);
        $customerAccountPortalEnableUpdater = $customerAccountPortalEnableUpdaterProphecy->reveal();

        $customerAccountRelationshipUpdaterListener = new CustomerAccountRelationshipsUpdaterListener($commandBus, $customerAccountPortalEnableUpdater);
        $actualData = $customerAccountRelationshipUpdaterListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernelViewWithoutRequestMethod_Post()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $customerAccountRelationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $customerAccountRelationshipProphecy->getType()->willReturn(new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON));
        $customerAccountRelationship = $customerAccountRelationshipProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerAccountRelationship);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $customerAccountPortalEnableUpdaterProphecy = $this->prophesize(CustomerAccountPortalEnableUpdater::class);
        $customerAccountPortalEnableUpdater = $customerAccountPortalEnableUpdaterProphecy->reveal();

        $customerAccountRelationshipUpdaterListener = new CustomerAccountRelationshipsUpdaterListener($commandBus, $customerAccountPortalEnableUpdater);
        $actualData = $customerAccountRelationshipUpdaterListener->onKernelView($event);

        $this->assertNull($actualData);
    }
}
