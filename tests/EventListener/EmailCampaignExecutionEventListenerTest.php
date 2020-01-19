<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Campaign;
use App\Enum\CampaignCategory;
use App\Enum\CampaignStatus;
use App\EventListener\EmailCampaignExecutionEventListener;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class EmailCampaignExecutionEventListenerTest extends TestCase
{
    public function testOnKernalRequestWithCampaignCategoryAsNotEmail()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaignProphecy->getCategory()->willReturn(new CampaignCategory(CampaignCategory::DIRECT_MAIL));
        $campaign = $campaignProphecy->reveal();

        $request = new Request();
        $request->attributes->set('data', $campaign);
        $request->setMethod('PUT');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $campaignQueueProphecy = $this->prophesize(Queue::class);
        $campaignQueue = $campaignQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $emailCampaignExecutionEventListener = new EmailCampaignExecutionEventListener($commandBus, $entityManager, $iriConverter, $campaignQueue, 'Asia/Singapore');
        $actualData = $emailCampaignExecutionEventListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalRequestWithoutCampaign()
    {
        $request = new Request();
        $request->attributes->set('data', null);
        $request->setMethod('PUT');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $campaignQueueProphecy = $this->prophesize(Queue::class);
        $campaignQueue = $campaignQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $emailCampaignExecutionEventListener = new EmailCampaignExecutionEventListener($commandBus, $entityManager, $iriConverter, $campaignQueue, 'Asia/Singapore');
        $actualData = $emailCampaignExecutionEventListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalRequestWithRequestMethodAsNotPUT()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaign = $campaignProphecy->reveal();

        $request = new Request();
        $request->attributes->set('data', $campaign);
        $request->setMethod('DELETE');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $campaignQueueProphecy = $this->prophesize(Queue::class);
        $campaignQueue = $campaignQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $emailCampaignExecutionEventListener = new EmailCampaignExecutionEventListener($commandBus, $entityManager, $iriConverter, $campaignQueue, 'Asia/Singapore');
        $actualData = $emailCampaignExecutionEventListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalView()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaignProphecy->getCategory()->willReturn(new CampaignCategory(CampaignCategory::EMAIL));
        $campaignProphecy->getStatus()->willReturn(new CampaignStatus(CampaignStatus::EXECUTED));
        $campaign = $campaignProphecy->reveal();

        $request = new Request();
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($campaign);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($campaign)->willReturn('testIri');
        $iriConverter = $iriConverterProphecy->reveal();

        $campaignQueueProphecy = $this->prophesize(Queue::class);
        $campaignQueueProphecy->push(new Job([
            'data' => [
                'campaign' => 'testIri',
            ],
            'type' => JobType::CAMPAIGN_EXECUTE,
        ]))->shouldBeCalled();
        $campaignQueue = $campaignQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $emailCampaignExecutionEventListener = new EmailCampaignExecutionEventListener($commandBus, $entityManager, $iriConverter, $campaignQueue, 'Asia/Singapore');
        $emailCampaignExecutionEventListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutCampaign()
    {
        $request = new Request();
        $request->setMethod('DELETE');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $campaignQueueProphecy = $this->prophesize(Queue::class);
        $campaignQueue = $campaignQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $emailCampaignExecutionEventListener = new EmailCampaignExecutionEventListener($commandBus, $entityManager, $iriConverter, $campaignQueue, 'Asia/Singapore');
        $actualData = $emailCampaignExecutionEventListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsNotPost()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaign = $campaignProphecy->reveal();

        $request = new Request();
        $request->setMethod('DELETE');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $campaignQueueProphecy = $this->prophesize(Queue::class);
        $campaignQueue = $campaignQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $emailCampaignExecutionEventListener = new EmailCampaignExecutionEventListener($commandBus, $entityManager, $iriConverter, $campaignQueue, 'Asia/Singapore');
        $actualData = $emailCampaignExecutionEventListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithCampaignCategoryAsNotEmail()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaignProphecy->getCategory()->willReturn(new CampaignCategory(CampaignCategory::SMS));
        $campaign = $campaignProphecy->reveal();

        $request = new Request();
        $request->setMethod('POST`');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $campaignQueueProphecy = $this->prophesize(Queue::class);
        $campaignQueue = $campaignQueueProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $emailCampaignExecutionEventListener = new EmailCampaignExecutionEventListener($commandBus, $entityManager, $iriConverter, $campaignQueue, 'Asia/Singapore');
        $actualData = $emailCampaignExecutionEventListener->onKernelView($event);

        $this->assertNull($actualData);
    }
}
