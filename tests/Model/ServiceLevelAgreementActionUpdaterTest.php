<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 24/4/19
 * Time: 12:24 PM.
 */

namespace App\Tests\Model;

use App\Entity\OpeningHoursSpecification;
use App\Entity\ServiceLevelAgreementAction;
use App\Entity\Ticket;
use App\Enum\TicketStatus;
use App\Model\ServiceLevelAgreementActionUpdater;
use App\Model\WorkingHourCalculator;
use App\Service\DateTimeHelper;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Rezzza\TimeTraveler;

class ServiceLevelAgreementActionUpdaterTest extends TestCase
{
    public function testSLAActionUpdaterGenerateFunctionWithTicketStatusAsAssigned()
    {
//        TimeTraveler::enable();
//        TimeTraveler::moveTo('2019-04-04 12:00:00');

        $date = new \DateTime('2019-02-02');
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getStatus()->willReturn(new TicketStatus(TicketStatus::ASSIGNED));
        $ticketProphecy->getDateOpened()->willReturn($date);
        $ticket = $ticketProphecy->reveal();

        $serviceLevelAgreementAction = new ServiceLevelAgreementAction();
        $serviceLevelAgreementAction->setTicket($ticket);
        $serviceLevelAgreementAction->setStartTime($date);

        $serviceLevelAgreementAction->setStatus(new TicketStatus('ASSIGNED'));
        $serviceLevelAgreementAction->setDescription('Assigned');

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($serviceLevelAgreementAction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $dateTimeHelperProphecy = $this->prophesize(DateTimeHelper::class);
        $dateTimeHelper = $dateTimeHelperProphecy->reveal();

        $workingHourCalculatorProphecy = $this->prophesize(WorkingHourCalculator::class);
        $workingHourCalculator = $workingHourCalculatorProphecy->reveal();

        $serviceLevelAgreementActionUpdater = new ServiceLevelAgreementActionUpdater($dateTimeHelper, $entityManager, $workingHourCalculator);
        $serviceLevelAgreementActionUpdater->generate($ticket, null);
    }

    public function testSLAActionUpdaterGenerateFunctionWithTicketStatusAsCancelled()
    {
        $date = new \DateTime('2019-02-02');
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getStatus()->willReturn(new TicketStatus(TicketStatus::CANCELLED));
        $ticketProphecy->getDateOpened()->willReturn($date);
        $ticket = $ticketProphecy->reveal();

        $serviceLevelAgreementAction = new ServiceLevelAgreementAction();
        $serviceLevelAgreementAction->setTicket($ticket);
        $serviceLevelAgreementAction->setStartTime($date);

        $serviceLevelAgreementAction->setStatus(new TicketStatus('CANCELLED'));
        $serviceLevelAgreementAction->setDescription('Cancelled');

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($serviceLevelAgreementAction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $dateTimeHelperProphecy = $this->prophesize(DateTimeHelper::class);
        $dateTimeHelper = $dateTimeHelperProphecy->reveal();

        $workingHourCalculatorProphecy = $this->prophesize(WorkingHourCalculator::class);
        $workingHourCalculator = $workingHourCalculatorProphecy->reveal();

        $serviceLevelAgreementActionUpdater = new ServiceLevelAgreementActionUpdater($dateTimeHelper, $entityManager, $workingHourCalculator);
        $serviceLevelAgreementActionUpdater->generate($ticket, null);
    }

    public function testSLAActionUpdaterGenerateFunctionWithTicketStatusAsInProgress()
    {
        $date = new \DateTime('2019-02-02');
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getStatus()->willReturn(new TicketStatus(TicketStatus::IN_PROGRESS));
        $ticketProphecy->getDateOpened()->willReturn($date);
        $ticket = $ticketProphecy->reveal();

        $serviceLevelAgreementAction = new ServiceLevelAgreementAction();
        $serviceLevelAgreementAction->setTicket($ticket);
        $serviceLevelAgreementAction->setStartTime($date);

        $serviceLevelAgreementAction->setStatus(new TicketStatus('IN_PROGRESS'));
        $serviceLevelAgreementAction->setDescription('In Progress');

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($serviceLevelAgreementAction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $dateTimeHelperProphecy = $this->prophesize(DateTimeHelper::class);
        $dateTimeHelper = $dateTimeHelperProphecy->reveal();

        $workingHourCalculatorProphecy = $this->prophesize(WorkingHourCalculator::class);
        $workingHourCalculator = $workingHourCalculatorProphecy->reveal();

        $serviceLevelAgreementActionUpdater = new ServiceLevelAgreementActionUpdater($dateTimeHelper, $entityManager, $workingHourCalculator);
        $serviceLevelAgreementActionUpdater->generate($ticket, null);
    }

    public function testSLAActionUpdaterGenerateFunctionWithTicketStatusAsCompleted()
    {
        $date = new \DateTime('2019-02-02');
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getStatus()->willReturn(new TicketStatus(TicketStatus::COMPLETED));
        $ticketProphecy->getDateOpened()->willReturn($date);
        $ticket = $ticketProphecy->reveal();

        $serviceLevelAgreementAction = new ServiceLevelAgreementAction();
        $serviceLevelAgreementAction->setTicket($ticket);
        $serviceLevelAgreementAction->setStartTime($date);

        $serviceLevelAgreementAction->setStatus(new TicketStatus('COMPLETED'));
        $serviceLevelAgreementAction->setDescription('Completed');

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($serviceLevelAgreementAction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $dateTimeHelperProphecy = $this->prophesize(DateTimeHelper::class);
        $dateTimeHelper = $dateTimeHelperProphecy->reveal();

        $workingHourCalculatorProphecy = $this->prophesize(WorkingHourCalculator::class);
        $workingHourCalculator = $workingHourCalculatorProphecy->reveal();

        $serviceLevelAgreementActionUpdater = new ServiceLevelAgreementActionUpdater($dateTimeHelper, $entityManager, $workingHourCalculator);
        $serviceLevelAgreementActionUpdater->generate($ticket, null);
    }

    public function testSLAActionUpdaterGenerateFunctionWithTicketStatusAsNew()
    {
        $date = new \DateTime('2019-02-02');
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getStatus()->willReturn(new TicketStatus(TicketStatus::NEW));
        $ticketProphecy->getDateOpened()->willReturn($date);
        $ticket = $ticketProphecy->reveal();

        $serviceLevelAgreementAction = new ServiceLevelAgreementAction();
        $serviceLevelAgreementAction->setTicket($ticket);
        $serviceLevelAgreementAction->setStartTime($date);

        $serviceLevelAgreementAction->setStatus(new TicketStatus('NEW'));
        $serviceLevelAgreementAction->setDescription('New');

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($serviceLevelAgreementAction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $dateTimeHelperProphecy = $this->prophesize(DateTimeHelper::class);
        $dateTimeHelper = $dateTimeHelperProphecy->reveal();

        $workingHourCalculatorProphecy = $this->prophesize(WorkingHourCalculator::class);
        $workingHourCalculator = $workingHourCalculatorProphecy->reveal();

        $serviceLevelAgreementActionUpdater = new ServiceLevelAgreementActionUpdater($dateTimeHelper, $entityManager, $workingHourCalculator);
        $serviceLevelAgreementActionUpdater->generate($ticket, null);
    }

    public function testSLAActionUpdaterGenerateFunctionWithTicketStatusAsPendingCustomerAction()
    {
        $date = new \DateTime('2019-02-02');
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getStatus()->willReturn(new TicketStatus(TicketStatus::PENDING_CUSTOMER_ACTION));
        $ticketProphecy->getDateOpened()->willReturn($date);
        $ticket = $ticketProphecy->reveal();

        $serviceLevelAgreementAction = new ServiceLevelAgreementAction();
        $serviceLevelAgreementAction->setTicket($ticket);
        $serviceLevelAgreementAction->setStartTime($date);

        $serviceLevelAgreementAction->setStatus(new TicketStatus('PENDING_CUSTOMER_ACTION'));
        $serviceLevelAgreementAction->setDescription('Pending Customer Action');

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($serviceLevelAgreementAction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $dateTimeHelperProphecy = $this->prophesize(DateTimeHelper::class);
        $dateTimeHelper = $dateTimeHelperProphecy->reveal();

        $workingHourCalculatorProphecy = $this->prophesize(WorkingHourCalculator::class);
        $workingHourCalculator = $workingHourCalculatorProphecy->reveal();

        $serviceLevelAgreementActionUpdater = new ServiceLevelAgreementActionUpdater($dateTimeHelper, $entityManager, $workingHourCalculator);
        $serviceLevelAgreementActionUpdater->generate($ticket, null);
    }

    public function testSLAActionUpdaterGenerateFunctionWithTicketStatusAsPendingBillingTeam()
    {
        $date = new \DateTime('2019-02-02');
        $ticketProphecy = $this->prophesize(Ticket::class);
        $ticketProphecy->getStatus()->willReturn(new TicketStatus(TicketStatus::PENDING_BILLING_TEAM));
        $ticketProphecy->getDateOpened()->willReturn($date);
        $ticket = $ticketProphecy->reveal();

        $serviceLevelAgreementAction = new ServiceLevelAgreementAction();
        $serviceLevelAgreementAction->setTicket($ticket);
        $serviceLevelAgreementAction->setStartTime($date);

        $serviceLevelAgreementAction->setStatus(new TicketStatus('PENDING_BILLING_TEAM'));
        $serviceLevelAgreementAction->setDescription('Pending Billing Team');

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($serviceLevelAgreementAction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $dateTimeHelperProphecy = $this->prophesize(DateTimeHelper::class);
        $dateTimeHelper = $dateTimeHelperProphecy->reveal();

        $workingHourCalculatorProphecy = $this->prophesize(WorkingHourCalculator::class);
        $workingHourCalculator = $workingHourCalculatorProphecy->reveal();

        $serviceLevelAgreementActionUpdater = new ServiceLevelAgreementActionUpdater($dateTimeHelper, $entityManager, $workingHourCalculator);
        $serviceLevelAgreementActionUpdater->generate($ticket, null);
    }

//    public function testUpdateFunctionWithSLA()
//    {
//
//        $openingHoursSpecificationProphecy = $this->prophesize(OpeningHoursSpecification::class);
//        $openingHoursSpecification = $openingHoursSpecificationProphecy->reveal();
//
//        $serviceLevelAgreementActionProphecy = $this->prophesize(ServiceLevelAgreementAction::class);
//        $serviceLevelAgreementActionProphecy->getOperationExclusions()->willReturn([$openingHoursSpecification]);
//        $serviceLevelAgreementAction = $serviceLevelAgreementActionProphecy->reveal();
//
//        $ticketProphecy = $this->prophesize(Ticket::class);
//        $ticketProphecy->getId()->willReturn(123);
//        $ticketProphecy->getServiceLevelAgreement()->willReturn($serviceLevelAgreementAction);
//        $ticket = $ticketProphecy->reveal();
//
//        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
//        $queryBuilderExpressionProphecy->eq('ticket.id', ':id')->shouldBeCalled();
//        $queryBuilderExpressionProphecy->isNull('action.value.value')->shouldBeCalled();
//        $queryBuilderExpressionProphecy->eq('action.status', ':status')->shouldBeCalled();
//        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();
//
//        $queryProphecy = $this->prophesize(AbstractQuery::class);
//        $queryProphecy->getResult()->willReturn([null]);
//        $query = $queryProphecy->reveal();
//
//        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
//        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
//        $queryBuilderProphecy->select('action')->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->leftJoin('action.ticket', 'ticket')->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->where($queryBuilderExpression->eq('ticket.id', ':id'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->andWhere($queryBuilderExpression->isNull('action.value.value'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->andWhere($queryBuilderExpression->eq('action.status', ':status'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->setParameters([
//            'id' => 123,
//            'status' => 'NEW'
//        ])->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->orderBy('action.dateModified', 'DESC')->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->getQuery()->willReturn($query);
//        $queryBuilder = $queryBuilderProphecy->reveal();
//
//        $serviceLevelAgreementActionRepositoryProphecy = $this->prophesize(ObjectRepository::class);
//        $serviceLevelAgreementActionRepositoryProphecy->createQueryBuilder('action')->willReturn($queryBuilder);
//        $serviceLevelAgreementActionRepository = $serviceLevelAgreementActionRepositoryProphecy->reveal();
//
//        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
//        $entityManagerProphecy->getRepository(ServiceLevelAgreementAction::class)->willReturn($serviceLevelAgreementActionRepository);
//        $entityManager = $entityManagerProphecy->reveal();
//
//
//        $dateTimeHelperProphecy = $this->prophesize(DateTimeHelper::class);
//        $dateTimeHelper = $dateTimeHelperProphecy->reveal();
//
//        $workingHourCalculatorProphecy = $this->prophesize(WorkingHourCalculator::class);
//        $workingHourCalculator = $workingHourCalculatorProphecy->reveal();
//
//
//        $serviceLevelAgreementActionUpdater = new ServiceLevelAgreementActionUpdater($dateTimeHelper,$entityManager,$workingHourCalculator);
//        $serviceLevelAgreementActionUpdater->update($ticket, 'NEW');
//    }
}
