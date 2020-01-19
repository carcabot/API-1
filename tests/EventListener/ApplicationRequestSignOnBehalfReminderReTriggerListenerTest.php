<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Activity;
use App\Entity\ApplicationRequest;
use App\Entity\EmailActivity;
use App\EventListener\ApplicationRequestSignOnBehalfReminderReTriggerListener;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ApplicationRequestSignOnBehalfReminderReTriggerListenerTest extends TestCase
{
    public function testOnKernalRequestWithoutApplicationRequest()
    {
        $request = new Request();
        $request->attributes->set('data', null);
        $request->setMethod('PUT');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $applicationRequestSignOnBehalfReminderReTriggerListener = new ApplicationRequestSignOnBehalfReminderReTriggerListener($emailsQueue, $iriConverter);
        $actualData = $applicationRequestSignOnBehalfReminderReTriggerListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalRequestWithRequestMethodAsNotPUT()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $request = new Request();
        $request->attributes->set('data', $applicationRequest);
        $request->setMethod('DELETE');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $applicationRequestSignOnBehalfReminderReTriggerListener = new ApplicationRequestSignOnBehalfReminderReTriggerListener($emailsQueue, $iriConverter);
        $actualData = $applicationRequestSignOnBehalfReminderReTriggerListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

//    public function testOnKernalView()
//    {
//        $activityProphecy = $this->prophesize(EmailActivity::class);
//        $activityProphecy->getId()->willReturn(123);
//        $activity = $activityProphecy->reveal();
//
//        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
//        $applicationRequestProphecy->getActivities()->willReturn([$activity]);
//        $applicationRequest = $applicationRequestProphecy->reveal();
//
//        $tempActivityProphecy = $this->prophesize(EmailActivity::class);
//        $tempActivityProphecy->getId()->willReturn(12345);
//        $tempActivity = $tempActivityProphecy->reveal();
//
//        $tempApplicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
//        $tempApplicationRequestProphecy->getActivities()->willReturn([$tempActivity]);
//        $tempApplicationRequest = $tempApplicationRequestProphecy->reveal();
//
//        $request = new Request();
//        $request->setMethod('PUT');
//        $request->attributes->set('data', $tempApplicationRequest);
//
//        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
//        $eventProphecy->getRequest()->willReturn($request);
//        $eventProphecy->getControllerResult()->willReturn($applicationRequest);
//        $event = $eventProphecy->reveal();
//
//        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
//        $iriConverterProphecy->getIriFromItem($applicationRequest)->willReturn('testIri');
//        $iriConverter = $iriConverterProphecy->reveal();
//
//        $emailsQueueProphecy = $this->prophesize(Queue::class);
//        $emailsQueueProphecy->push(new Job([
//            'data' => [
//                'applicationRequest' => 'testIri',
//            ],
//            'type' => JobType::APPLICATION_REQUEST_SUBMITTED_PENDING_AUTHORIZATION,
//        ]))->shouldBeCalled();
//        $emailsQueue = $emailsQueueProphecy->reveal();
//
//        $applicationRequestSignOnBehalfReminderReTriggerListener = new ApplicationRequestSignOnBehalfReminderReTriggerListener($emailsQueue, $iriConverter);
//        $applicationRequestSignOnBehalfReminderReTriggerListener->onKernelRequest($event);
//        $applicationRequestSignOnBehalfReminderReTriggerListener->onKernelView($event);
//    }

    public function testOnKernalViewWithoutApplicationRequest()
    {
        $request = new Request();
        $request->setMethod('PUT');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $applicationRequestSignOnBehalfReminderReTriggerListener = new ApplicationRequestSignOnBehalfReminderReTriggerListener($emailsQueue, $iriConverter);
        $actualData = $applicationRequestSignOnBehalfReminderReTriggerListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsNotPut()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $request = new Request();
        $request->setMethod('DELETE');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($applicationRequest);
        $event = $eventProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $applicationRequestSignOnBehalfReminderReTriggerListener = new ApplicationRequestSignOnBehalfReminderReTriggerListener($emailsQueue, $iriConverter);
        $actualData = $applicationRequestSignOnBehalfReminderReTriggerListener->onKernelView($event);

        $this->assertNull($actualData);
    }
}
