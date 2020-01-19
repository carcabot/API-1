<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\Contract\UpdatePointCreditsActions as UpdateContractPointCreditsActions;
use App\Domain\Command\CustomerAccount\UpdateMoneyCreditsActions as UpdateCustomerAccountMoneyCreditsActions;
use App\Domain\Command\CustomerAccount\UpdatePointCreditsActions as UpdateCustomerAccountPointCreditsActions;
use App\Domain\Command\UpdateCreditsAction\CreateReciprocalAction;
use App\Entity\Contract;
use App\Entity\CreditsTransaction;
use App\Entity\CustomerAccount;
use App\Entity\EarnContractCreditsAction;
use App\Entity\EarnCustomerCreditsAction;
use App\Entity\MoneyCreditsTransaction;
use App\Entity\PointCreditsTransaction;
use App\Entity\QuantitativeValue;
use App\Entity\UpdateCreditsAction;
use App\Entity\User;
use App\Entity\WithdrawCreditsAction;
use App\EventListener\UpdateCreditsActionEventSubscriber;
use App\Service\AuthenticationHelper;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UpdateCreditsActionEventSubscriberTest extends TestCase
{
    public function testUpdateCreditsAdditionWithTransactionAsMoneyAndObjectAsCustomer()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccount = $customerAccountProphecy->reveal();

        $creditsTransactionProphecy = $this->prophesize(MoneyCreditsTransaction::class);
        $creditsTransaction = $creditsTransactionProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(EarnCustomerCreditsAction::class);
        $updateCreditsActionProphecy->getObject()->willReturn($customerAccount);
        $updateCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransaction);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getCustomerAccount()->willReturn($customerAccount);
        $user = $userProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $event = $eventProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelperProphecy->getAuthenticatedUser()->willReturn($user);
        $authHelper = $authHelperProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateCustomerAccountMoneyCreditsActions($customerAccount, $updateCreditsAction))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $updateCreditsActionEventSubscriber->updateCreditsAddition($event);
    }

    public function testUpdateCreditsAdditionWithTransactionAsPointAndObjectAsContract()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccount = $customerAccountProphecy->reveal();

        $creditsTransactionProphecy = $this->prophesize(PointCreditsTransaction::class);
        $creditsTransaction = $creditsTransactionProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contract = $contractProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(EarnContractCreditsAction::class);
        $updateCreditsActionProphecy->getObject()->willReturn($contract);
        $updateCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransaction);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getCustomerAccount()->willReturn($customerAccount);
        $user = $userProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $event = $eventProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelperProphecy->getAuthenticatedUser()->willReturn($user);
        $authHelper = $authHelperProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateContractPointCreditsActions($contract, $updateCreditsAction))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $updateCreditsActionEventSubscriber->updateCreditsAddition($event);
    }

    public function testUpdateCreditsAdditionWithoutUpdateCreditsAction()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelper = $authHelperProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $actualData = $updateCreditsActionEventSubscriber->updateCreditsAddition($event);

        $this->assertNull($actualData);
    }

    public function testUpdateCreditsAdditionWithRequestMethodAsNotPost()
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

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelper = $authHelperProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $actualData = $updateCreditsActionEventSubscriber->updateCreditsAddition($event);

        $this->assertNull($actualData);
    }

    public function testUpdateCreditsAdditionWithNoAuthenticatedUser()
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

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelperProphecy->getAuthenticatedUser()->willReturn(null);
        $authHelper = $authHelperProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You have no power here.');

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $updateCreditsActionEventSubscriber->updateCreditsAddition($event);
    }

    public function testUpdateCreditsSubtractionWithObjectAsCustomerAndTransactionAsPoint()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getPointCreditsBalance()->willReturn(new QuantitativeValue('123'));
        $customerAccount = $customerAccountProphecy->reveal();

        $creditsTransactionProphecy = $this->prophesize(PointCreditsTransaction::class);
        $creditsTransactionProphecy->getAmount()->willReturn(new QuantitativeValue('123'));
        $creditsTransaction = $creditsTransactionProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(WithdrawCreditsAction::class);
        $updateCreditsActionProphecy->getObject()->willReturn($customerAccount);
        $updateCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransaction);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getCustomerAccount()->willReturn($customerAccount);
        $user = $userProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $tempUpdateCreditsActionProphecy = $this->prophesize(EarnCustomerCreditsAction::class);
        $tempUpdateCreditsActionProphecy->getObject()->willReturn($customerAccount);
        $tempUpdateCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransaction);
        $tempUpdateCreditsAction = $tempUpdateCreditsActionProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateCustomerAccountPointCreditsActions($customerAccount, $updateCreditsAction))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateCustomerAccountPointCreditsActions($customerAccount, $tempUpdateCreditsAction))->shouldBeCalled();
        $commandBusProphecy->handle(new CreateReciprocalAction($updateCreditsAction))->willReturn($tempUpdateCreditsAction);
        $commandBus = $commandBusProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelperProphecy->getAuthenticatedUser()->willReturn($user);
        $authHelper = $authHelperProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($tempUpdateCreditsAction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $updateCreditsActionEventSubscriber->updateCreditsSubtraction($event);
    }

    public function testUpdateCreditsSubtractionWithObjectAsCustomerAndTransactionAsMoney()
    {
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getMoneyCreditsBalance()->willReturn(new QuantitativeValue('123'));
        $customerAccount = $customerAccountProphecy->reveal();

        $creditsTransactionProphecy = $this->prophesize(CreditsTransaction::class);
        $creditsTransaction = $creditsTransactionProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(WithdrawCreditsAction::class);
        $updateCreditsActionProphecy->getObject()->willReturn($customerAccount);
        $updateCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransaction);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $userProphecy = $this->prophesize(User::class);
        $userProphecy->getCustomerAccount()->willReturn($customerAccount);
        $user = $userProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelperProphecy->getAuthenticatedUser()->willReturn($user);
        $authHelper = $authHelperProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Insufficient credits.');

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $updateCreditsActionEventSubscriber->updateCreditsSubtraction($event);
    }

    public function testUpdateCreditsSubtractionWithoutUpdateCreditsAction()
    {
        $request = new Request();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn(null);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelper = $authHelperProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $actualData = $updateCreditsActionEventSubscriber->updateCreditsSubtraction($event);

        $this->assertNull($actualData);
    }

    public function testUpdateCreditsSubtractionWithRequestMethodAsNotPost()
    {
        $updateCreditsActionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelper = $authHelperProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $actualData = $updateCreditsActionEventSubscriber->updateCreditsSubtraction($event);

        $this->assertNull($actualData);
    }

    public function testUpdateCreditsSubtractionWithNotAuthenticatedUser()
    {
        $updateCreditsActionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getControllerResult()->willReturn($updateCreditsAction);
        $eventProphecy->getRequest()->willReturn($request);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $authHelperProphecy = $this->prophesize(AuthenticationHelper::class);
        $authHelperProphecy->getAuthenticatedUser()->willReturn(null);
        $authHelper = $authHelperProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('You have no power here.');

        $updateCreditsActionEventSubscriber = new UpdateCreditsActionEventSubscriber($authHelper, $commandBus, $entityManager);
        $updateCreditsActionEventSubscriber->updateCreditsSubtraction($event);
    }
}
