<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Ticket;
use App\EventListener\TicketEventSubscriber;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class TicketEventSubscriberTest extends TestCase
{
    public function testSendEmail()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticket = $ticketProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($ticket);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($ticket)->willReturn('testIri');
        $iriConverter = $iriConverterProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueueProphecy->push(new Job([
            'data' => [
                'ticket' => 'testIri',
            ],
            'type' => JobType::TICKET_CREATED,
            'ticket' => [
                '@id' => 'testIri',
            ],
        ]))->shouldBeCalled();
        $disqueQueue = $disqueQueueProphecy->reveal();

        $ticketEventSubscriber = new TicketEventSubscriber($disqueQueue, $iriConverter);
        $ticketEventSubscriber->sendEmail($event);
    }

    public function testSendEmailWithoutTicket()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $ticketEventSubscriber = new TicketEventSubscriber($disqueQueue, $iriConverter);
        $actualData = $ticketEventSubscriber->sendEmail($event);

        $this->assertNull($actualData);
    }

    public function testSendEmailWithRequestMethodAsNotPost()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticket = $ticketProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($ticket);
        $event = $eventProphecy->reveal();

        $disqueQueueProphecy = $this->prophesize(Queue::class);
        $disqueQueue = $disqueQueueProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $ticketEventSubscriber = new TicketEventSubscriber($disqueQueue, $iriConverter);
        $actualData = $ticketEventSubscriber->sendEmail($event);

        $this->assertNull($actualData);
    }
}
