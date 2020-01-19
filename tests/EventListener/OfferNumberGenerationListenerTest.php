<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\Offer\UpdateOfferNumber;
use App\Entity\Offer;
use App\EventListener\OfferNumberGenerationListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class OfferNumberGenerationListenerTest extends TestCase
{
    public function testOnKernalView()
    {
        $offerProphecy = $this->prophesize(Offer::class);
        $offer = $offerProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($offer);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateOfferNumber($offer))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $offerNumberGenerationListener = new OfferNumberGenerationListener($commandBus, $entityManager);
        $offerNumberGenerationListener->onKernelView($event);
    }

    public function testOnKernalViewWithRequestMethodAsNotPost()
    {
        $offerProphecy = $this->prophesize(Offer::class);
        $offer = $offerProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($offer);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $offerNumberGenerationListener = new OfferNumberGenerationListener($commandBus, $entityManager);
        $actualData = $offerNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithoutOffer()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $offerNumberGenerationListener = new OfferNumberGenerationListener($commandBus, $entityManager);
        $actualData = $offerNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnPostWrite()
    {
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $offerNumberGenerationListener = new OfferNumberGenerationListener($commandBus, $entityManager);
        $offerNumberGenerationListener->setLocked(true);
        $offerNumberGenerationListener->onPostWrite($event);
    }
}
