<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\Lead\UpdateLeadNumber;
use App\Entity\Lead;
use App\EventListener\LeadNumberGenerationListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class LeadNumberGenerationListenerTest extends TestCase
{
    public function testOnKernalView()
    {
        $leadProphecy = $this->prophesize(Lead::class);
        $leadProphecy->getLeadNumber()->willReturn(null);
        $lead = $leadProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($lead);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateLeadNumber($lead))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $leadNumberGenerationListener = new LeadNumberGenerationListener($commandBus, $entityManager);
        $leadNumberGenerationListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutLead()
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

        $leadNumberGenerationListener = new LeadNumberGenerationListener($commandBus, $entityManager);
        $actualData = $leadNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsNotPost()
    {
        $leadProphecy = $this->prophesize(Lead::class);
        $lead = $leadProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($lead);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $leadNumberGenerationListener = new LeadNumberGenerationListener($commandBus, $entityManager);
        $actualData = $leadNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithLeadNumberAsNotNull()
    {
        $leadProphecy = $this->prophesize(Lead::class);
        $leadProphecy->getLeadNumber()->willReturn(123);
        $lead = $leadProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($lead);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $leadNumberGenerationListener = new LeadNumberGenerationListener($commandBus, $entityManager);
        $actualData = $leadNumberGenerationListener->onKernelView($event);

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

        $leadNumberGenerationListener = new LeadNumberGenerationListener($commandBus, $entityManager);
        $leadNumberGenerationListener->setLocked(true);
        $leadNumberGenerationListener->onPostWrite($event);
    }
}
