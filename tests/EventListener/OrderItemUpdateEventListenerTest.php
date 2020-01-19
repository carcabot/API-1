<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\OrderItem\UpdateUnitPrice;
use App\Entity\OrderItem;
use App\EventListener\OrderItemUpdateEventListener;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class OrderItemUpdateEventListenerTest extends TestCase
{
    public function testOnKernalView()
    {
        $orderItemProphecy = $this->prophesize(OrderItem::class);
        $orderItem = $orderItemProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($orderItem);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateUnitPrice($orderItem))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $orderItemUpdateEventListener = new OrderItemUpdateEventListener($commandBus);
        $orderItemUpdateEventListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutOrderItem()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $orderItemUpdateEventListener = new OrderItemUpdateEventListener($commandBus);
        $actualData = $orderItemUpdateEventListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsNotPost()
    {
        $orderItemProphecy = $this->prophesize(OrderItem::class);
        $orderItem = $orderItemProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($orderItem);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $orderItemUpdateEventListener = new OrderItemUpdateEventListener($commandBus);
        $actualData = $orderItemUpdateEventListener->onKernelView($event);

        $this->assertNull($actualData);
    }
}
