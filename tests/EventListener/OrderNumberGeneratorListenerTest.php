<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\Order\CalculateTotalPoints;
use App\Domain\Command\Order\UpdateOrderNumber;
use App\Entity\Order;
use App\Enum\OrderStatus;
use App\EventListener\OrderNumberGeneratorListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class OrderNumberGeneratorListenerTest extends TestCase
{
    public function testOnKernalRequestWithoutOrder()
    {
        $order = new Order();
        $order->setOrderStatus(new OrderStatus(OrderStatus::DRAFT));

        $request = new Request();
        $request->setMethod('POST');
        $request->attributes->set('data', null);

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $orderNumberGeneratorListener = new OrderNumberGeneratorListener($commandBus, $entityManager);
        $actualData = $orderNumberGeneratorListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalRequestWithRequestMethodAsNotPost()
    {
        $order = new Order();
        $order->setOrderStatus(new OrderStatus(OrderStatus::DRAFT));

        $request = new Request();
        $request->setMethod('DELETE');
        $request->attributes->set('data', $order);

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $orderNumberGeneratorListener = new OrderNumberGeneratorListener($commandBus, $entityManager);
        $actualData = $orderNumberGeneratorListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalView()
    {
        $orderProphecy = $this->prophesize(Order::class);
        $orderProphecy->getOrderStatus()->willReturn(new OrderStatus(OrderStatus::PAYMENT_DUE));
        $order = $orderProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($order);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateOrderNumber($order))->shouldBeCalled();
        $commandBusProphecy->handle(new CalculateTotalPoints($order))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $orderNumberGeneratorListener = new OrderNumberGeneratorListener($commandBus, $entityManager);
        $orderNumberGeneratorListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutOrder()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(null);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $orderNumberGeneratorListener = new OrderNumberGeneratorListener($commandBus, $entityManager);
        $actualData = $orderNumberGeneratorListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalVeiwWithRequestMethodAsNotPOST()
    {
        $orderProphecy = $this->prophesize(Order::class);
        $order = $orderProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($order);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $orderNumberGeneratorListener = new OrderNumberGeneratorListener($commandBus, $entityManager);
        $actualData = $orderNumberGeneratorListener->onKernelView($event);

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

        $orderNumberGeneratorListener = new OrderNumberGeneratorListener($commandBus, $entityManager);
        $orderNumberGeneratorListener->setLocked(true);
        $orderNumberGeneratorListener->onPostWrite($event);
    }
}
