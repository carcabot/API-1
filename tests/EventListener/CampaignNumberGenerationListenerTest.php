<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\Campaign\UpdateCampaignNumber;
use App\Entity\Campaign;
use App\EventListener\CampaignNumberGenerationListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class CampaignNumberGenerationListenerTest extends TestCase
{
    public function testOnKernalView()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaign = $campaignProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($campaign);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateCampaignNumber($campaign))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $campaignNumberGenerationListener = new CampaignNumberGenerationListener($commandBus, $entityManager);
        $campaignNumberGenerationListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutCampaign()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(null);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $campaignNumberGenerationListener = new CampaignNumberGenerationListener($commandBus, $entityManager);
        $actualData = $campaignNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsDelete()
    {
        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaign = $campaignProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($campaign);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $campaignNumberGenerationListener = new CampaignNumberGenerationListener($commandBus, $entityManager);
        $actualData = $campaignNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnPostWrite()
    {
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event = $eventProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $campaignNumberGenerationListener = new CampaignNumberGenerationListener($commandBus, $entityManager);
        $campaignNumberGenerationListener->setLocked(true);
        $campaignNumberGenerationListener->onPostWrite($event);
    }
}
