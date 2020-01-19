<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\UpdateCreditsAction;
use App\EventListener\UpdateCreditsActionCreationListener;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class UpdateCreditsActionCreationListenerTest extends TestCase
{
    public function testOnKernalView()
    {
        $updateCreditsActionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateTransaction($updateCreditsAction))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $updateCreditsActionCreationListener = new UpdateCreditsActionCreationListener($commandBus);
        $updateCreditsActionCreationListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutUpdateCreditsAction()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $updateCreditsActionCreationListener = new UpdateCreditsActionCreationListener($commandBus);
        $actualData = $updateCreditsActionCreationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsNotPost()
    {
        $updateCreditsActionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateTransaction($updateCreditsAction));
        $commandBus = $commandBusProphecy->reveal();

        $updateCreditsActionCreationListener = new UpdateCreditsActionCreationListener($commandBus);
        $actualData = $updateCreditsActionCreationListener->onKernelView($event);

        $this->assertNull($actualData);
    }
}
