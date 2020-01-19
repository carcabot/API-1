<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\OfferListItem\DeleteOfferListItem;
use App\Entity\OfferListItem;
use App\EventListener\OfferListItemDeletionEventListener;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class OfferListItemDeletionEventListenerTest extends TestCase
{
    public function testOnKernalView()
    {
        $offerListItemProphecy = $this->prophesize(OfferListItem::class);
        $offerListItem = $offerListItemProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($offerListItem);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new DeleteOfferListItem($offerListItem))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $offerListItemDeletionEventListener = new OfferListItemDeletionEventListener($commandBus);
        $offerListItemDeletionEventListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutOfferListItem()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $offerListItemDeletionEventListener = new OfferListItemDeletionEventListener($commandBus);
        $actualData = $offerListItemDeletionEventListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsNotDelete()
    {
        $offerListItemProphecy = $this->prophesize(OfferListItem::class);
        $offerListItem = $offerListItemProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($offerListItem);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $offerListItemDeletionEventListener = new OfferListItemDeletionEventListener($commandBus);
        $actualData = $offerListItemDeletionEventListener->onKernelView($event);

        $this->assertNull($actualData);
    }
}
