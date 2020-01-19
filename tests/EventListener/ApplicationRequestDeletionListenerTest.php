<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use App\EventListener\ApplicationRequestDeletionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApplicationRequestDeletionListenerTest extends TestCase
{
    public function testOnKernalView()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($applicationRequest);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Unable to DELETE the application request.');

        $applicationRequestDeletionListener = new ApplicationRequestDeletionListener();
        $applicationRequestDeletionListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutApplicationRequest()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(null);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $applicationRequestDeletionListener = new ApplicationRequestDeletionListener();
        $actualData = $applicationRequestDeletionListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsPOST()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($applicationRequest);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $applicationRequestDeletionListener = new ApplicationRequestDeletionListener();
        $actualData = $applicationRequestDeletionListener->onKernelView($event);

        $this->assertNull($actualData);
    }
}
