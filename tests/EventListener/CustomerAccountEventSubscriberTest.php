<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\CustomerAccount\UpdateSalesRepresentativeAccountNumber;
use App\Entity\Corporation;
use App\Entity\CustomerAccount;
use App\Entity\EmployeeRole;
use App\EventListener\CustomerAccountEventSubscriber;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class CustomerAccountEventSubscriberTest extends TestCase
{
    public function testGenerateSalesRepresentativeAccountNumber()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $employeeCustomerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $employeeCustomerAccountProphecy->getAccountNumber()->willReturn(null);
        $employeeCustomerAccount = $employeeCustomerAccountProphecy->reveal();

        $employeeRoleProphecy = $this->prophesize(EmployeeRole::class);
        $employeeRoleProphecy->getEmployee()->willReturn($employeeCustomerAccount);
        $employeeRole = $employeeRoleProphecy->reveal();

        $corporationProphecy = $this->prophesize(Corporation::class);
        $corporationProphecy->getEmployees()->willReturn([$employeeRole]);
        $corporation = $corporationProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn(['PARTNER']);
        $customerAccountProphecy->getCorporationDetails()->willReturn($corporation);
        $customerAccount = $customerAccountProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerAccount);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateSalesRepresentativeAccountNumber($customerAccount, $employeeCustomerAccount))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManagerProphecy->persist($employeeCustomerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountEventSubscriber = new CustomerAccountEventSubscriber($commandBus, $entityManager, 'Asia/Singapore');
        $customerAccountEventSubscriber->generateSalesRepresentativeAccountNumber($event);
    }

    public function testGenerateSalesRepresentativeAccountNumberWithoutCustomerAccount()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountEventSubscriber = new CustomerAccountEventSubscriber($commandBus, $entityManager, 'Asia/Singapore');
        $actualData = $customerAccountEventSubscriber->generateSalesRepresentativeAccountNumber($event);

        $this->assertNull($actualData);
    }

    public function testGenerateSalesRepresentativeAccountNumberWithRequestMethodAsDelete()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccount = $customerAccountProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerAccount);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountEventSubscriber = new CustomerAccountEventSubscriber($commandBus, $entityManager, 'Asia/Singapore');
        $actualData = $customerAccountEventSubscriber->generateSalesRepresentativeAccountNumber($event);

        $this->assertNull($actualData);
    }

    public function testGenerateSalesRepresentativeAccountNumberWithAccountCategoryAsCustomer()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn(['Customer']);
        $customerAccount = $customerAccountProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerAccount);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountEventSubscriber = new CustomerAccountEventSubscriber($commandBus, $entityManager, 'Asia/Singapore');
        $actualData = $customerAccountEventSubscriber->generateSalesRepresentativeAccountNumber($event);

        $this->assertNull($actualData);
    }

    public function testGenerateSalesRepresentativeAccountNumberWithoutCorporationDetails()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getCategories()->willReturn(['PARTNER']);
        $customerAccountProphecy->getCorporationDetails()->willReturn(null);
        $customerAccount = $customerAccountProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerAccount);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $customerAccountEventSubscriber = new CustomerAccountEventSubscriber($commandBus, $entityManager, 'Asia/Singapore');
        $actualData = $customerAccountEventSubscriber->generateSalesRepresentativeAccountNumber($event);

        $this->assertNull($actualData);
    }
}
