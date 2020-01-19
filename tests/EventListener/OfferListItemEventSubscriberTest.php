<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\OfferListItem\UpdateInventoryLevel;
use App\Entity\OfferListItem;
use App\EventListener\OfferListItemEventSubscriber;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class OfferListItemEventSubscriberTest extends TestCase
{
    public function testUpdateInventoryLevel()
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
        $commandBusProphecy->handle(new UpdateInventoryLevel($offerListItem))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $offerListItemEventSubscriber = new OfferListItemEventSubscriber($commandBus);
        $offerListItemEventSubscriber->updateInventoryLevel($event);
    }

    public function testUpdateInventoryLevelWithoutOfferListItem()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $offerListItemEventSubscriber = new OfferListItemEventSubscriber($commandBus);
        $actualData = $offerListItemEventSubscriber->updateInventoryLevel($event);

        $this->assertNull($actualData);
    }

    public function testUpdateInventoryLevelWithRequestMethodAsDelete()
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
        $commandBus = $commandBusProphecy->reveal();

        $offerListItemEventSubscriber = new OfferListItemEventSubscriber($commandBus);
        $actualData = $offerListItemEventSubscriber->updateInventoryLevel($event);

        $this->assertNull($actualData);
    }
}
