<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumber;
use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestToken;
use App\Domain\Command\ApplicationRequest\UpdateEmailActivity;
use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use App\EventListener\ApplicationRequestNumberGenerationListener;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ApplicationRequestNumberGenerationListenerTest extends TestCase
{
    public function testOnKernalRequestWithoutApplicationRequest()
    {
        $request = new Request();
        $request->attributes->set('data', null);
        $request->setMethod('POST');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $applicationRequestNumberGenerationListener = new ApplicationRequestNumberGenerationListener($commandBus, $emailsQueue, $entityManager, $iriConverter);
        $actualData = $applicationRequestNumberGenerationListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalRequestWithRequestMethodAsDelete()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $request = new Request();
        $request->attributes->set('data', $applicationRequest);
        $request->setMethod('DELETE');

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $applicationRequestNumberGenerationListener = new ApplicationRequestNumberGenerationListener($commandBus, $emailsQueue, $entityManager, $iriConverter);
        $actualData = $applicationRequestNumberGenerationListener->onKernelRequest($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalView()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::PENDING));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($applicationRequest);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateApplicationRequestToken($applicationRequest))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateEmailActivity($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $applicationRequestNumberGenerationListener = new ApplicationRequestNumberGenerationListener($commandBus, $emailsQueue, $entityManager, $iriConverter);
        $applicationRequestNumberGenerationListener->onKernelView($event);
    }

    public function testOnKernalViewWithoutApplicationRequest()
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

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $applicationRequestNumberGenerationListener = new ApplicationRequestNumberGenerationListener($commandBus, $emailsQueue, $entityManager, $iriConverter);
        $actualData = $applicationRequestNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithMethodAsMethodDelete()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($applicationRequest);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $applicationRequestNumberGenerationListener = new ApplicationRequestNumberGenerationListener($commandBus, $emailsQueue, $entityManager, $iriConverter);
        $actualData = $applicationRequestNumberGenerationListener->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnPostWrite()
    {
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::PENDING));
        $applicationRequest = $applicationRequestProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($applicationRequest);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateApplicationRequestNumber($applicationRequest))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateApplicationRequestToken($applicationRequest))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateEmailActivity($applicationRequest))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($applicationRequest)->willReturn('testIri');
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueueProphecy->push(new Job([
            'data' => [
                'applicationRequest' => 'testIri',
            ],
            'type' => JobType::APPLICATION_REQUEST_SUBMITTED_PENDING_AUTHORIZATION,
        ]))->shouldBeCalled();
        $emailsQueue = $emailsQueueProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->beginTransaction()->shouldBeCalled();
        $connectionProphecy->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;')->shouldBeCalled();
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $applicationRequestNumberGenerationListener = new ApplicationRequestNumberGenerationListener($commandBus, $emailsQueue, $entityManager, $iriConverter);
        $applicationRequestNumberGenerationListener->onKernelView($event);
        $applicationRequestNumberGenerationListener->onPostWrite($event);
    }

    public function testOnPostWriteWithoutApplicationRequest()
    {
        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->commit()->shouldBeCalled();
        $connection = $connectionProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getConnection()->willReturn($connection);
        $entityManager = $entityManagerProphecy->reveal();

        $applicationRequestNumberGenerationListener = new ApplicationRequestNumberGenerationListener($commandBus, $emailsQueue, $entityManager, $iriConverter);
        $applicationRequestNumberGenerationListener->setLocked(true);
        $actualData = $applicationRequestNumberGenerationListener->onPostWrite($event);

        $this->assertNull($actualData);
    }
}
