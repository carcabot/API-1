<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\TicketCategory\UpdateTicketTypeForChildren;
use App\Domain\Command\TicketCategory\UpdateTicketTypeFromParent;
use App\Entity\TicketCategory;
use App\EventListener\TicketMainCategoryEventSubscriber;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class TicketMainCategoryEventSubscriberTest extends TestCase
{
    public function testUpdateTicketTypes()
    {
        $ticketCategoryProphecy = $this->prophesize(TicketCategory::class);
        $ticketCategory = $ticketCategoryProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($ticketCategory);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateTicketTypeFromParent($ticketCategory))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateTicketTypeForChildren($ticketCategory))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $ticketMainCategoryEventSubscriber = new TicketMainCategoryEventSubscriber($commandBus);
        $ticketMainCategoryEventSubscriber->updateTicketTypes($event);
    }

    public function testUpdateTicketTypesWithoutTicketCategory()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $ticketMainCategoryEventSubscriber = new TicketMainCategoryEventSubscriber($commandBus);
        $actualData = $ticketMainCategoryEventSubscriber->updateTicketTypes($event);

        $this->assertNull($actualData);
    }

    public function testUpdateTicketTypesWithoutRequestMethodAsPost()
    {
        $ticketCategoryProphecy = $this->prophesize(TicketCategory::class);
        $ticketCategory = $ticketCategoryProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($ticketCategory);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $ticketMainCategoryEventSubscriber = new TicketMainCategoryEventSubscriber($commandBus);
        $actualData = $ticketMainCategoryEventSubscriber->updateTicketTypes($event);

        $this->assertNull($actualData);
    }
}
