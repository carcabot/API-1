<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Domain\Command\CustomerAccount\UpdateBlacklistNotes;
use App\Domain\Command\CustomerAccount\UpdateBlacklistStatus;
use App\Entity\CustomerAccount;
use App\Entity\CustomerBlacklist;
use App\EventListener\CustomerBlacklistEventSubscriber;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerBlacklistEventSubscriberTest extends TestCase
{
    public function testUpdateCustomerWithCustomerNotOnBlacklist()
    {
        $customerBlacklistProphecy = $this->prophesize(CustomerBlacklist::class);
        $customerBlacklistProphecy->getIdentification()->willReturn('testIdentification');
        $customerBlacklistProphecy->getName()->willReturn('testName');
        $customerBlacklist = $customerBlacklistProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerBlacklist);
        $event = $eventProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getDateBlacklisted()->willReturn(null);
        $customerAccount = $customerAccountProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateBlacklistStatus($customerBlacklist, $customerAccount))->willReturn(false);
        $commandBus = $commandBusProphecy->reveal();

        $queryBuilderComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $queryBuilderComparison = $queryBuilderComparisonProphecy->reveal();

        $queryBuilderAndXProphecy = $this->prophesize(Expr\Andx::class);
        $queryBuilderAndX = $queryBuilderAndXProphecy->reveal();

        $queryBuilderOrXProphecy = $this->prophesize(Expr\Orx::class);
        $queryBuilderOrX = $queryBuilderOrXProphecy->reveal();

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('personIdentity.value', ':identity')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('person.name', ':name')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('corporationIdentity.value', ':identity')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('corporation.name', ':name')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->andX($queryBuilderComparison, $queryBuilderComparison)->shouldBeCalled()->willReturn($queryBuilderAndX);
        $queryBuilderExpressionProphecy->andX($queryBuilderComparison, $queryBuilderComparison)->shouldBeCalled()->willReturn($queryBuilderAndX);
        $queryBuilderExpressionProphecy->orX($queryBuilderAndX, $queryBuilderAndX)->shouldBeCalled()->willReturn($queryBuilderOrX);
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([$customerAccount]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.personDetails', 'person')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('customer.corporationDetails', 'corporation')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('person.identifiers', 'personIdentity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('corporation.identifiers', 'corporationIdentity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderOrX)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('identity', 'testIdentification')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', 'testName')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Customer is not on the blacklist.');

        $customerBlacklistEventSubscriber = new CustomerBlacklistEventSubscriber($commandBus, $entityManager);
        $customerBlacklistEventSubscriber->updateCustomer($event);
    }

    public function testUpdateCustomerWithCustomerAlreadyOnBlacklist()
    {
        $customerBlacklistProphecy = $this->prophesize(CustomerBlacklist::class);
        $customerBlacklistProphecy->getIdentification()->willReturn('testIdentification');
        $customerBlacklistProphecy->getName()->willReturn('testName');
        $customerBlacklist = $customerBlacklistProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerBlacklist);
        $event = $eventProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->getDateBlacklisted()->willReturn(new \DateTime('2019-02-02'));
        $customerAccount = $customerAccountProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateBlacklistStatus($customerBlacklist, $customerAccount))->willReturn(false);
        $commandBus = $commandBusProphecy->reveal();

        $queryBuilderComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $queryBuilderComparison = $queryBuilderComparisonProphecy->reveal();

        $queryBuilderAndXProphecy = $this->prophesize(Expr\Andx::class);
        $queryBuilderAndX = $queryBuilderAndXProphecy->reveal();

        $queryBuilderOrXProphecy = $this->prophesize(Expr\Orx::class);
        $queryBuilderOrX = $queryBuilderOrXProphecy->reveal();

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('personIdentity.value', ':identity')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('person.name', ':name')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('corporationIdentity.value', ':identity')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('corporation.name', ':name')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->andX($queryBuilderComparison, $queryBuilderComparison)->shouldBeCalled()->willReturn($queryBuilderAndX);
        $queryBuilderExpressionProphecy->andX($queryBuilderComparison, $queryBuilderComparison)->shouldBeCalled()->willReturn($queryBuilderAndX);
        $queryBuilderExpressionProphecy->orX($queryBuilderAndX, $queryBuilderAndX)->shouldBeCalled()->willReturn($queryBuilderOrX);
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([$customerAccount]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.personDetails', 'person')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('customer.corporationDetails', 'corporation')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('person.identifiers', 'personIdentity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('corporation.identifiers', 'corporationIdentity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderOrX)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('identity', 'testIdentification')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', 'testName')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Customer is already on the blacklist.');

        $customerBlacklistEventSubscriber = new CustomerBlacklistEventSubscriber($commandBus, $entityManager);
        $customerBlacklistEventSubscriber->updateCustomer($event);
    }

    public function testUpdateCustomerWithValidBlackList()
    {
        $customerBlacklistProphecy = $this->prophesize(CustomerBlacklist::class);
        $customerBlacklistProphecy->getIdentification()->willReturn('testIdentification');
        $customerBlacklistProphecy->getName()->willReturn('testName');
        $customerBlacklist = $customerBlacklistProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerBlacklist);
        $event = $eventProphecy->reveal();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccount = $customerAccountProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateBlacklistStatus($customerBlacklist, $customerAccount))->willReturn(true);
        $commandBusProphecy->handle(new UpdateBlacklistNotes($customerBlacklist, $customerAccount))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $queryBuilderComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $queryBuilderComparison = $queryBuilderComparisonProphecy->reveal();

        $queryBuilderAndXProphecy = $this->prophesize(Expr\Andx::class);
        $queryBuilderAndX = $queryBuilderAndXProphecy->reveal();

        $queryBuilderOrXProphecy = $this->prophesize(Expr\Orx::class);
        $queryBuilderOrX = $queryBuilderOrXProphecy->reveal();

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('personIdentity.value', ':identity')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('person.name', ':name')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('corporationIdentity.value', ':identity')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('corporation.name', ':name')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->andX($queryBuilderComparison, $queryBuilderComparison)->shouldBeCalled()->willReturn($queryBuilderAndX);
        $queryBuilderExpressionProphecy->andX($queryBuilderComparison, $queryBuilderComparison)->shouldBeCalled()->willReturn($queryBuilderAndX);
        $queryBuilderExpressionProphecy->orX($queryBuilderAndX, $queryBuilderAndX)->shouldBeCalled()->willReturn($queryBuilderOrX);
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([$customerAccount]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.personDetails', 'person')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('customer.corporationDetails', 'corporation')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('person.identifiers', 'personIdentity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('corporation.identifiers', 'corporationIdentity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderOrX)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('identity', 'testIdentification')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', 'testName')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $customerBlacklistEventSubscriber = new CustomerBlacklistEventSubscriber($commandBus, $entityManager);
        $customerBlacklistEventSubscriber->updateCustomer($event);
    }

    public function testUpdateCustomerWithoutCustomer()
    {
        $customerBlacklistProphecy = $this->prophesize(CustomerBlacklist::class);
        $customerBlacklistProphecy->getIdentification()->willReturn('testIdentification');
        $customerBlacklistProphecy->getName()->willReturn('testName');
        $customerBlacklist = $customerBlacklistProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_POST);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerBlacklist);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $queryBuilderComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $queryBuilderComparison = $queryBuilderComparisonProphecy->reveal();

        $queryBuilderAndXProphecy = $this->prophesize(Expr\Andx::class);
        $queryBuilderAndX = $queryBuilderAndXProphecy->reveal();

        $queryBuilderOrXProphecy = $this->prophesize(Expr\Orx::class);
        $queryBuilderOrX = $queryBuilderOrXProphecy->reveal();

        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
        $queryBuilderExpressionProphecy->eq('personIdentity.value', ':identity')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('person.name', ':name')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('corporationIdentity.value', ':identity')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->eq('corporation.name', ':name')->shouldBeCalled()->willReturn($queryBuilderComparison);
        $queryBuilderExpressionProphecy->andX($queryBuilderComparison, $queryBuilderComparison)->shouldBeCalled()->willReturn($queryBuilderAndX);
        $queryBuilderExpressionProphecy->andX($queryBuilderComparison, $queryBuilderComparison)->shouldBeCalled()->willReturn($queryBuilderAndX);
        $queryBuilderExpressionProphecy->orX($queryBuilderAndX, $queryBuilderAndX)->shouldBeCalled()->willReturn($queryBuilderOrX);
        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();

        $queryProphecy = $this->prophesize(AbstractQuery::class);
        $queryProphecy->getResult()->willReturn([]);
        $query = $queryProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
        $queryBuilderProphecy->leftJoin('customer.personDetails', 'person')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('customer.corporationDetails', 'corporation')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('person.identifiers', 'personIdentity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->leftJoin('corporation.identifiers', 'corporationIdentity')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->where($queryBuilderOrX)->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('identity', 'testIdentification')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->setParameter('name', 'testName')->shouldBeCalled()->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->getQuery()->willReturn($query);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $customerAccountRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $customerAccountRepositoryProphecy->createQueryBuilder('customer')->willReturn($queryBuilder);
        $customerAccountRepository = $customerAccountRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(CustomerAccount::class)->willReturn($customerAccountRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No customer found.');

        $customerBlacklistEventSubscriber = new CustomerBlacklistEventSubscriber($commandBus, $entityManager);
        $customerBlacklistEventSubscriber->updateCustomer($event);
    }

    public function testUpdateCustomerWithRequestMethodAsNotPOST()
    {
        $customerBlacklistProphecy = $this->prophesize(CustomerBlacklist::class);
        $customerBlacklist = $customerBlacklistProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMethod()->willReturn(Request::METHOD_DELETE);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn($customerBlacklist);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $customerBlacklistEventSubscriber = new CustomerBlacklistEventSubscriber($commandBus, $entityManager);
        $actualData = $customerBlacklistEventSubscriber->updateCustomer($event);

        $this->assertNull($actualData);
    }

    public function testUpdateCustomerWithoutCustomerBlacklist()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $request = $requestProphecy->reveal();

        $eventProphecy = $this->prophesize(GetResponseForControllerResultEvent::class);
        $eventProphecy->getRequest()->willReturn($request);
        $eventProphecy->getControllerResult()->willReturn(null);
        $event = $eventProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $customerBlacklistEventSubscriber = new CustomerBlacklistEventSubscriber($commandBus, $entityManager);
        $actualData = $customerBlacklistEventSubscriber->updateCustomer($event);

        $this->assertNull($actualData);
    }
}
