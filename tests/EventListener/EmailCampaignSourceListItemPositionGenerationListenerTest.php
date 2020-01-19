<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\Campaign\ResetEmailCampaignSourceListItemPositions;
use App\Entity\Campaign;
use App\Enum\CampaignStatus;
use App\EventListener\EmailCampaignSourceListItemPositionGenerationListener;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class EmailCampaignSourceListItemPositionGenerationListenerTest extends TestCase
{
    public function testOnKernalView()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaignProphecy->getStatus()->willReturn(new CampaignStatus(CampaignStatus::SCHEDULED));
        $campaign = $campaignProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_PUT);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($campaign);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new ResetEmailCampaignSourceListItemPositions($campaign))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $emailCampaignSourceListItemPositionGeneration = new EmailCampaignSourceListItemPositionGenerationListener($commandBus);
        $emailCampaignSourceListItemPositionGeneration->onKernelView($event);
    }

    public function testOnKernalViewWithCampaignStatusAsNotScheduled()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaignProphecy->getStatus()->willReturn(new CampaignStatus(CampaignStatus::CANCELLED));
        $campaign = $campaignProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_PUT);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($campaign);
        $event = $eventProphecy->reveal();

        $emailCampaignSourceListItemPositionGeneration = new EmailCampaignSourceListItemPositionGenerationListener($commandBus);
        $actualData = $emailCampaignSourceListItemPositionGeneration->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsNotPUT()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaign = $campaignProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($campaign);
        $event = $eventProphecy->reveal();

        $emailCampaignSourceListItemPositionGeneration = new EmailCampaignSourceListItemPositionGenerationListener($commandBus);
        $actualData = $emailCampaignSourceListItemPositionGeneration->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithoutCampaign()
    {
        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $emailCampaignSourceListItemPositionGeneration = new EmailCampaignSourceListItemPositionGenerationListener($commandBus);
        $actualData = $emailCampaignSourceListItemPositionGeneration->onKernelView($event);

        $this->assertNull($actualData);
    }
}
