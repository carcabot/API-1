<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\TariffDailyRate\ValidateTariffDailyRate;
use App\Entity\TariffDailyRate;
use App\EventListener\TariffDailyRateGenerationListener;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class TariffDailyRateGenerationListenerTest extends TestCase
{
    public function testOnKernalViewPreWrite()
    {
        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRate = $tariffDailyRateProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($tariffDailyRate);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new ValidateTariffDailyRate($tariffDailyRate))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $tariffDailyRateGenerationListener = new TariffDailyRateGenerationListener($commandBus);
        $tariffDailyRateGenerationListener->onKernelViewPreWrite($event);
    }

    public function testOnKernalViewPreWriteWithoutTariffDailyRate()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $tariffDailyRateGenerationListener = new TariffDailyRateGenerationListener($commandBus);
        $actualData = $tariffDailyRateGenerationListener->onKernelViewPreWrite($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewPreWriteWithRequestMEthodAsNotPost()
    {
        $tariffDailyRateProphecy = $this->prophesize(TariffDailyRate::class);
        $tariffDailyRate = $tariffDailyRateProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($tariffDailyRate);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $tariffDailyRateGenerationListener = new TariffDailyRateGenerationListener($commandBus);
        $actualData = $tariffDailyRateGenerationListener->onKernelViewPreWrite($event);

        $this->assertNull($actualData);
    }
}
