<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\CustomerAccount\UpdateAccountNumber;
use App\Domain\Command\CustomerAccount\UpdateReferralCode;
use App\Entity\CustomerAccount;
use App\EventListener\CustomerAccountNumberGenerationListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class CustomerAccountNumberGenerationListenerTest extends TestCase
{
    public function testOnKernalView()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getAccountNumber()->willReturn(null);
        $customerAccount = $customerAccountProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($customerAccount);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateAccountNumber($customerAccount))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateReferralCode($customerAccount))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountNumberGenerationListener = new CustomerAccountNumberGenerationListener($commandBus, $entityManager);
        $customerAccountNumberGenerationListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutCustomerAccount()
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

        $customerAccountNumberGenerationListener = new CustomerAccountNumberGenerationListener($commandBus, $entityManager);
        $actualData = $customerAccountNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithoutRequestMethodPost()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccount = $customerAccountProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($customerAccount);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountNumberGenerationListener = new CustomerAccountNumberGenerationListener($commandBus, $entityManager);
        $actualData = $customerAccountNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithCustomerAccountNumberAsNotNull()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getAccountNumber()->willReturn('123456');
        $customerAccount = $customerAccountProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($customerAccount);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountNumberGenerationListener = new CustomerAccountNumberGenerationListener($commandBus, $entityManager);
        $actualData = $customerAccountNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnPostWrite()
    {
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountNumberGenerationListener = new CustomerAccountNumberGenerationListener($commandBus, $entityManager);
        $customerAccountNumberGenerationListener->setLocked(true);
        $customerAccountNumberGenerationListener->onPostWrite($event);
    }
}
