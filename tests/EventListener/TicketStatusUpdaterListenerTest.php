<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\ServiceLevelAgreementAction\GenerateServiceLevelAgreementAction;
use App\Domain\Command\Ticket\UpdateServiceLevelAgreement;
use App\Entity\Ticket;
use App\Entity\TicketServiceLevelAgreement;
use App\Enum\TicketStatus;
use App\EventListener\TicketStatusUpdaterListener;
use App\Model\ServiceLevelAgreementTimerCalculator;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class TicketStatusUpdaterListenerTest extends TestCase
{
    public function testOnKernalRequestWithoutTicket()
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->attributes->set('data', null);

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $serviceLevelAgreementTimerCalculatorProphecy = $this->prophesize(ServiceLevelAgreementTimerCalculator::class);
        $serviceLevelAgreementTimerCalculator = $serviceLevelAgreementTimerCalculatorProphecy->reveal();

        $ticketStatusUpdaterListener = new TicketStatusUpdaterListener($commandBus, $serviceLevelAgreementTimerCalculator);
        $actualData = $ticketStatusUpdaterListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalRequestWithRequestMethodAsNotPost()
    {
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticket = $ticketProphecy->reveal();

        $request = new Request();
        $request->setMethod('DELETE');
        $request->attributes->set('data', $ticket);

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $serviceLevelAgreementTimerCalculatorProphecy = $this->prophesize(ServiceLevelAgreementTimerCalculator::class);
        $serviceLevelAgreementTimerCalculator = $serviceLevelAgreementTimerCalculatorProphecy->reveal();

        $ticketStatusUpdaterListener = new TicketStatusUpdaterListener($commandBus, $serviceLevelAgreementTimerCalculator);
        $actualData = $ticketStatusUpdaterListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewPreWriteWithNoSla()
    {
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getStatus()->willReturn(new TicketStatus(TicketStatus::ASSIGNED));
        $ticketProphecy->getServiceLevelAgreement()->willReturn(null);
        $ticket = $ticketProphecy->reveal();

        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($ticket);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateServiceLevelAgreement($ticket))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $serviceLevelAgreementTimerCalculatorProphecy = $this->prophesize(ServiceLevelAgreementTimerCalculator::class);
        $serviceLevelAgreementTimerCalculator = $serviceLevelAgreementTimerCalculatorProphecy->reveal();

        $ticketStatusUpdaterListener = new TicketStatusUpdaterListener($commandBus, $serviceLevelAgreementTimerCalculator);
        $ticketStatusUpdaterListener->onKernelViewPreWrite($event);
    }

    public function testOnKernalViewPreWriteWithSla()
    {
        $serviceLevelAgreementProphecy = $this->prophesize(TicketServiceLevelAgreement::class);
        $serviceLevelAgreement = $serviceLevelAgreementProphecy->reveal();

        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getStatus()->willReturn(new TicketStatus(TicketStatus::COMPLETED));
        $ticketProphecy->getServiceLevelAgreement()->willReturn($serviceLevelAgreement);
        $ticket = $ticketProphecy->reveal();

        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($ticket);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new GenerateServiceLevelAgreementAction($ticket, null))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $serviceLevelAgreementTimerCalculatorProphecy = $this->prophesize(ServiceLevelAgreementTimerCalculator::class);
        $serviceLevelAgreementTimerCalculator = $serviceLevelAgreementTimerCalculatorProphecy->reveal();

        $ticketStatusUpdaterListener = new TicketStatusUpdaterListener($commandBus, $serviceLevelAgreementTimerCalculator);
        $ticketStatusUpdaterListener->onKernelViewPreWrite($event);
    }

    public function testOnKernalViewPreWriteWithoutTicket()
    {
        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $serviceLevelAgreementTimerCalculatorProphecy = $this->prophesize(ServiceLevelAgreementTimerCalculator::class);
        $serviceLevelAgreementTimerCalculator = $serviceLevelAgreementTimerCalculatorProphecy->reveal();

        $ticketStatusUpdaterListener = new TicketStatusUpdaterListener($commandBus, $serviceLevelAgreementTimerCalculator);
        $actualData = $ticketStatusUpdaterListener->onKernelViewPreWrite($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewPreWriteWithRequestMethodAsNotPost()
    {
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticket = $ticketProphecy->reveal();

        $request = new Request();
        $request->setMethod('DELETE');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($ticket);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $serviceLevelAgreementTimerCalculatorProphecy = $this->prophesize(ServiceLevelAgreementTimerCalculator::class);
        $serviceLevelAgreementTimerCalculator = $serviceLevelAgreementTimerCalculatorProphecy->reveal();

        $ticketStatusUpdaterListener = new TicketStatusUpdaterListener($commandBus, $serviceLevelAgreementTimerCalculator);
        $actualData = $ticketStatusUpdaterListener->onKernelViewPreWrite($event);

        $this->assertNull($actualData);
    }
}
