<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 23/4/19
 * Time: 4:12 PM.
 */

namespace App\Tests\Model;

use App\Domain\Command\Contract\UpdatePointCreditsActions;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\Contract;
use App\Entity\CreditsScheme;
use App\Entity\EarnContractCreditsAction;
use App\Entity\QuantitativeValue;
use App\Entity\UpdateCreditsAction;
use App\Enum\ActionStatus;
use App\Model\ContractCreditsUpdater;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use League\Tactician\CommandBus;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * @group time-sensitive
 */
class ContractCreditsUpdaterTest extends TestCase
{
//    public function test()
//    {
//
//        $creditSchemeProphecy = $this->prophesize(CreditsScheme::class);
//        $creditSchemeProphecy->getSchemeId()->willReturn('GI');
//        $creditScheme = $creditSchemeProphecy->reveal();
//
//        $updateCreditsActionProphecy = $this->prophesize(EarnContractCreditsAction::class);
//        $updateCreditsActionProphecy->getScheme()->willReturn($creditScheme);
//        $updateCreditsAction = $updateCreditsActionProphecy->reveal();
//
//        $contractProphecy = $this->prophesize(Contract::class);
//        $contractProphecy->getPointCreditsActions()->willReturn([$updateCreditsAction]);
//        $contract = $contractProphecy->reveal();
//
//        $commandBusProphecy = $this->prophesize(CommandBus::class);
//        $commandBus = $commandBusProphecy->reveal();
//
//        $loggerProphecy = $this->prophesize(Logger::class);
//        $logger = $loggerProphecy->reveal();
//
//        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
//        $entityManager = $entityManagerProphecy->reveal();
//
//        $contractCreditsUpdater = new ContractCreditsUpdater($commandBus, $entityManager, $logger, '');
    // //       $result = $contractCreditsUpdater->hasEarnedGIROPoints($contract);
//
    //  //      $this->assertTrue($result);
//
//    }
//
//    public function DATETIMEISSUE()
//    {
//
//        $data = [];
//        $data['schemeId'] = 'TEST';
//        $data['date'] = '2019-05-05T16:00:00.000Z';
//        $data['contractNumber'] = 'SWCC123456';
//
//        $date =  new \DateTime('2019-05-05T16:00:00.000Z');
//        $creditSchemeProphecy = $this->prophesize(CreditsScheme::class);
//        $creditSchemeProphecy->getSchemeId()->willReturn('GI');
//        $creditSchemeProphecy->getAmount()->willReturn(new QuantitativeValue('testAmount'));
//        $creditSchemeProphecy->setIsBasedOn($creditSchemeProphecy)->shouldBeCalled();
//        $creditScheme = $creditSchemeProphecy->reveal();
//
//
//        $updateCreditsActionProphecy = $this->prophesize(EarnContractCreditsAction::class);
//        $updateCreditsActionProphecy->getScheme()->willReturn($creditScheme);
//        $updateCreditsAction = $updateCreditsActionProphecy->reveal();
//
//        $contractProphecy = $this->prophesize(Contract::class);
//        $contractProphecy->getPointCreditsActions()->willReturn([$updateCreditsAction]);
//        $contract = $contractProphecy->reveal();
//
//        $contractRepositoryProphecy = $this->prophesize(EntityRepository::class);
//        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn($contract);
//        $contractRepository = $contractRepositoryProphecy->reveal();
//
//        $queryExpressionComparisonProphecy = $this->prophesize(Expr\Comparison::class);
//        $queryExpressionComparison = $queryExpressionComparisonProphecy->reveal();
//
//        $queryBuilderExpressionProphecy = $this->prophesize(Expr::class);
//        $queryBuilderExpressionProphecy->eq('scheme.schemeId', ':schemeId')->shouldBeCalled();
//        $queryBuilderExpressionProphecy->lte('scheme.validFrom', ':date')->shouldBeCalled();
//        $queryBuilderExpressionProphecy->gte('scheme.validThrough', ':date')->willReturn($queryExpressionComparison);
//        $queryBuilderExpressionProphecy->isNull('scheme.validThrough')->willReturn('scheme.validThrough IS NULL');
//        $queryBuilderExpressionProphecy->orX($queryExpressionComparison, 'scheme.validThrough IS NULL')->shouldBeCalled();
//        $queryBuilderExpression = $queryBuilderExpressionProphecy->reveal();
//
//        $queryProphecy = $this->prophesize(AbstractQuery::class);
//        $queryProphecy->getResult()->willReturn([$creditScheme]);
//        $query = $queryProphecy->reveal();
//
//        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
//        $queryBuilderProphecy->expr()->willReturn($queryBuilderExpression);
//        $queryBuilderProphecy->where($queryBuilderExpression->eq('scheme.schemeId', ':schemeId'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->andWhere($queryBuilderExpression->lte('scheme.validFrom', ':date'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->andWhere($queryBuilderExpression->orX($queryExpressionComparison,'scheme.validThrough IS NULL'))->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->setParameter('schemeId', 'TEST')->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->setParameter('date',$date)->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->orderBy('scheme.dateModified', 'DESC')->shouldBeCalled()->willReturn($queryBuilderProphecy);
//        $queryBuilderProphecy->getQuery()->willReturn($query);
//        $queryBuilder = $queryBuilderProphecy->reveal();
//
//        $creditSchemeRepositoryProphecy = $this->prophesize(EntityRepository::class);
//        $creditSchemeRepositoryProphecy->createQueryBuilder('scheme')->willReturn($queryBuilder);
//        $creditSchemeRepository = $creditSchemeRepositoryProphecy->reveal();
//
//        $earnCreditsAction = new EarnContractCreditsAction();
//        $earnCreditsAction->setAmount('testAmount');
//        $earnCreditsAction->setEndTime(new \DateTime());
//        $earnCreditsAction->setStartTime(new \DateTime());
//        $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));
//        $earnCreditsAction->setObject($contract);
//        $earnCreditsAction->setScheme($creditScheme);
//
//
//        $commandBusProphecy = $this->prophesize(CommandBus::class);
//        $commandBusProphecy->handle(new UpdateTransaction($earnCreditsAction))->shouldBeCalled();
//        $commandBusProphecy->handle(new UpdatePointCreditsActions($contract, $earnCreditsAction))->shouldBeCalled();
//        $commandBus = $commandBusProphecy->reveal();
//
//        $loggerProphecy = $this->prophesize(Logger::class);
//        $logger = $loggerProphecy->reveal();
//
//        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
//        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
//        $entityManagerProphecy->getRepository(CreditsScheme::class)->willReturn($creditSchemeRepository);
//        $entityManagerProphecy->persist($creditScheme)->shouldBeCalled();
//        $entityManagerProphecy->persist($earnCreditsAction)->shouldBeCalled();
//        $entityManagerProphecy->persist($contract)->shouldBeCalled();
//        $entityManagerProphecy->flush()->shouldBeCalled();
//        $entityManager = $entityManagerProphecy->reveal();
//
//        $contractCreditsUpdater = new ContractCreditsUpdater($commandBus, $entityManager, $logger, '');
//
//        $contractCreditsUpdater->processArrayData([$data]);
//
//    }

    public function testContractCreditsUpdaterWithoutSchemeIdAndDate()
    {
        $data = [];
        $data['date'] = '2019-05-05T16:00:00.000Z';
        $data['contractNumber'] = 'SWCC123456';

        $date = new \DateTime('2019-05-05T16:00:00.000Z');
        $creditSchemeProphecy = $this->prophesize(CreditsScheme::class);
        $creditSchemeProphecy->getSchemeId()->willReturn('GI');
        $creditSchemeProphecy->getAmount()->willReturn(new QuantitativeValue(null));
        $creditScheme = $creditSchemeProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(EarnContractCreditsAction::class);
        $updateCreditsActionProphecy->getScheme()->willReturn($creditScheme);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getPointCreditsActions()->willReturn([$updateCreditsAction]);
        $contract = $contractProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $loggerProphecy->error('Scheme ID and date not specified.')->shouldBeCalled();
        $logger = $loggerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $contractCreditsUpdater = new ContractCreditsUpdater($commandBus, $entityManager, $logger, '');

        $contractCreditsUpdater->processArrayData([$data]);
    }

    public function testContractCreditsUpdaterWithoutContract()
    {
        $data = [];
        $data['schemeId'] = 'TEST';
        $data['date'] = '2019-05-05T16:00:00.000Z';
        $data['contractNumber'] = 'SWCC123456';

        $contractRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn(null);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $loggerProphecy->error('Contract SWCC123456 does not exist.')->shouldBeCalled();
        $logger = $loggerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $contractCreditsUpdater = new ContractCreditsUpdater($commandBus, $entityManager, $logger, '');

        $contractCreditsUpdater->processArrayData([$data]);
    }

    public function testContractCreditsUpdaterWithoutContractNumber()
    {
        $data = [];
        $data['schemeId'] = 'TEST';
        $data['date'] = '2019-05-05T16:00:00.000Z';

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $loggerProphecy->error('Contract not specified.')->shouldBeCalled();
        $logger = $loggerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $contractCreditsUpdater = new ContractCreditsUpdater($commandBus, $entityManager, $logger, '');

        $contractCreditsUpdater->processArrayData([$data]);
    }

    public function testContractCreditsUpdaterHasEarnedGIROPointsFunction()
    {
        $data = [];
        $data['schemeId'] = 'GI';
        $data['date'] = '2019-05-05T16:00:00.000Z';
        $data['contractNumber'] = 'SWCC123456';

        $date = new \DateTime('2019-05-05T16:00:00.000Z');
        $creditSchemeProphecy = $this->prophesize(CreditsScheme::class);
        $creditSchemeProphecy->getSchemeId()->willReturn('GI');
        $creditScheme = $creditSchemeProphecy->reveal();

        $updateCreditsActionProphecy = $this->prophesize(EarnContractCreditsAction::class);
        $updateCreditsActionProphecy->getScheme()->willReturn($creditScheme);
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getPointCreditsActions()->willReturn([$updateCreditsAction]);
        $contract = $contractProphecy->reveal();

        $contractRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn($contract);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $contractCreditsUpdater = new ContractCreditsUpdater($commandBus, $entityManager, $logger, '');

        $contractCreditsUpdater->processArrayData([$data]);
    }
}
