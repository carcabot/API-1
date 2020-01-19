<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Disque\JobType;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\UpdateCreditsAction;
use App\Entity\WithdrawCreditsAction;
use App\Enum\ContractStatus;
use App\EventListener\WithdrawCreditsActionEventSubscriber;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class WithdrawCreditsActionEventSubscriberTest extends TestCase
{
    public function testOnKernalView()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::ACTIVE));
        $contract = $contractProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getDefaultCreditsContract()->willReturn(null);
        $customerAccountProphecy->getContracts()->willReturn([$contract]);
        $customerAccount = $customerAccountProphecy->reveal();

        $withdrawCreditsActionProphecy = $this->prophesize(WithdrawCreditsAction::class);
        $withdrawCreditsActionProphecy->getContract()->willReturn(null);
        $withdrawCreditsActionProphecy->getObject()->willReturn($customerAccount);
        $withdrawCreditsActionProphecy->setContract($contract)->shouldBeCalled();
        $withdrawCreditsAction = $withdrawCreditsActionProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($withdrawCreditsAction);
        $event = $eventProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($withdrawCreditsAction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $webServicesQueueProphecy = $this->prophesize(Queue::class);
        $webServicesQueue = $webServicesQueueProphecy->reveal();

        $withdrawCreditsActionsEventSubscriber = new WithdrawCreditsActionEventSubscriber($entityManager, $webServicesQueue);
        $withdrawCreditsActionsEventSubscriber->onKernelView($event);
    }

    public function testOnKernalViewWithoutUpdateCreditsAction()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $webServicesQueueProphecy = $this->prophesize(Queue::class);
        $webServicesQueue = $webServicesQueueProphecy->reveal();

        $withdrawCreditsActionsEventSubscriber = new WithdrawCreditsActionEventSubscriber($entityManager, $webServicesQueue);
        $actualData = $withdrawCreditsActionsEventSubscriber->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithoutWithdrawCreditsAction()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $event = $eventProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $webServicesQueueProphecy = $this->prophesize(Queue::class);
        $webServicesQueue = $webServicesQueueProphecy->reveal();

        $withdrawCreditsActionsEventSubscriber = new WithdrawCreditsActionEventSubscriber($entityManager, $webServicesQueue);
        $actualData = $withdrawCreditsActionsEventSubscriber->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testOnKernalViewWithRequestMethodAsNotPost()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $event = $eventProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $webServicesQueueProphecy = $this->prophesize(Queue::class);
        $webServicesQueue = $webServicesQueueProphecy->reveal();

        $withdrawCreditsActionsEventSubscriber = new WithdrawCreditsActionEventSubscriber($entityManager, $webServicesQueue);
        $actualData = $withdrawCreditsActionsEventSubscriber->onKernelView($event);

        $this->assertNull($actualData);
    }

    public function testPostWithdrawalCreditsActionWebService()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $withdrawCreditsActionProphecy = $this->prophesize(WithdrawCreditsAction::class);
        $withdrawCreditsActionProphecy->getId()->willReturn(123);
        $withdrawCreditsAction = $withdrawCreditsActionProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($withdrawCreditsAction);
        $event = $eventProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $webServicesQueueProphecy = $this->prophesize(Queue::class);
        $webServicesQueueProphecy->push(new Job([
            'data' => [
                'id' => 123,
            ],
            'type' => JobType::WITHDRAW_CREDITS_ACTION_SUBMIT,
        ]))->shouldBeCalled();
        $webServicesQueue = $webServicesQueueProphecy->reveal();

        $withdrawCreditsActionsEventSubscriber = new WithdrawCreditsActionEventSubscriber($entityManager, $webServicesQueue);
        $withdrawCreditsActionsEventSubscriber->postWithdrawCreditsActionWebService($event);
    }

    public function testPostWithdrawalCreditsActionWebServiceWithoutUpdateCreditsAction()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $webServicesQueueProphecy = $this->prophesize(Queue::class);
        $webServicesQueue = $webServicesQueueProphecy->reveal();

        $withdrawCreditsActionsEventSubscriber = new WithdrawCreditsActionEventSubscriber($entityManager, $webServicesQueue);
        $actualData = $withdrawCreditsActionsEventSubscriber->postWithdrawCreditsActionWebService($event);

        $this->assertNull($actualData);
    }

    public function testPostWithdrawalCreditsActionWebServiceWithoutWithdrawCreditsAction()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $event = $eventProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $webServicesQueueProphecy = $this->prophesize(Queue::class);
        $webServicesQueue = $webServicesQueueProphecy->reveal();

        $withdrawCreditsActionsEventSubscriber = new WithdrawCreditsActionEventSubscriber($entityManager, $webServicesQueue);
        $actualData = $withdrawCreditsActionsEventSubscriber->postWithdrawCreditsActionWebService($event);

        $this->assertNull($actualData);
    }

    public function testPostWithdrawalCreditsActionWebServiceWithRequestMethodAsNotPost()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $event = $eventProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $webServicesQueueProphecy = $this->prophesize(Queue::class);
        $webServicesQueue = $webServicesQueueProphecy->reveal();

        $withdrawCreditsActionsEventSubscriber = new WithdrawCreditsActionEventSubscriber($entityManager, $webServicesQueue);
        $actualData = $withdrawCreditsActionsEventSubscriber->postWithdrawCreditsActionWebService($event);

        $this->assertNull($actualData);
    }
}
