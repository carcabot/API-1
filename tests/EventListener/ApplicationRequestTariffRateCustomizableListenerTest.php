<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\ApplicationRequest\UpdateCustomized;
use App\Entity\ApplicationRequest;
use App\EventListener\ApplicationRequestTariffRateCustomizableListener;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ApplicationRequestTariffRateCustomizableListenerTest extends TestCase
{
    public function testOnKernalView()
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

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateCustomized($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $applicationRequestTariffRateCustomizableListener = new ApplicationRequestTariffRateCustomizableListener($commandBus);
        $applicationRequestTariffRateCustomizableListener->onKernelView($event);
    }

    public function testOnKernalViewWithRequestMethod_Post()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_CONNECT);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($applicationRequest);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $applicationRequestTariffRateCustomizableListener = new ApplicationRequestTariffRateCustomizableListener($commandBus);
        $actualData = $applicationRequestTariffRateCustomizableListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithoutApplicationRequest()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(null);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $applicationRequestTariffRateCustomizableListener = new ApplicationRequestTariffRateCustomizableListener($commandBus);
        $actualData = $applicationRequestTariffRateCustomizableListener->onKernelView($event);

        $this->assertNull($actualData);
    }
}
