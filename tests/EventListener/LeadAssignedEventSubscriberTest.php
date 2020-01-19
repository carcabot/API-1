<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Lead;
use App\Entity\User;
use App\EventListener\LeadAssignedEventSubscriber;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class LeadAssignedEventSubscriberTest extends TestCase
{
    public function tesOnKernalRequestWithoutLead()
    {
        $request = new Request();
        $request->attributes->set('data', null);
        $request->setMethod('PUT');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $leadAssignedEventSubscriber = new LeadAssignedEventSubscriber($emailsQueue, $iriConverter);
        $actualData = $leadAssignedEventSubscriber->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function tesOnKernalRequestWithRequestMethodAsNotPost()
    {
        $leadProphecy = $this->prophesize(Lead::class);
        $lead = $leadProphecy->reveal();

        $request = new Request();
        $request->attributes->set('data', $lead);
        $request->setMethod('DELETE');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $leadAssignedEventSubscriber = new LeadAssignedEventSubscriber($emailsQueue, $iriConverter);
        $actualData = $leadAssignedEventSubscriber->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testSendEmailNotification()
    {
        $userProphecy = $this->prophesize(User::class);
        $user = $userProphecy->reveal();

        $leadProphecy = $this->prophesize(Lead::class);
        $leadProphecy->getAssignee()->willReturn($user);
        $lead = $leadProphecy->reveal();

        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($lead);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($lead)->willReturn('testIri');
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueueProphecy->push(new Job([
            'data' => [
                'lead' => 'testIri',
            ],
            'type' => JobType::LEAD_ASSIGNED_ASSIGNEE,
            'lead' => [
                '@id' => 'testIri',
            ],
        ]))->shouldBeCalled();
        $emailsQueue = $emailsQueueProphecy->reveal();

        $leadAssignedEventSubscriber = new LeadAssignedEventSubscriber($emailsQueue, $iriConverter);
        $leadAssignedEventSubscriber->sendEmailNotification($event);
    }

    public function testSendEmailNotificationWithoutLead()
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(null);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $leadAssignedEventSubscriber = new LeadAssignedEventSubscriber($emailsQueue, $iriConverter);
        $actualData = $leadAssignedEventSubscriber->sendEmailNotification($event);

        $this->assertNull($actualData);
    }

    public function testSendEmailNotificationWithRequestMethodAsNotPOST()
    {
        $leadProphecy = $this->prophesize(Lead::class);
        $lead = $leadProphecy->reveal();

        $request = new Request();
        $request->setMethod('DELETE');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(null);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $leadAssignedEventSubscriber = new LeadAssignedEventSubscriber($emailsQueue, $iriConverter);
        $actualData = $leadAssignedEventSubscriber->sendEmailNotification($event);

        $this->assertNull($actualData);
    }
}
