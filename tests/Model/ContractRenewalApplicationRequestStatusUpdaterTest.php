<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 30/4/19
 * Time: 1:44 PM.
 */

namespace App\Tests\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Enum\ApplicationRequestStatus;
use App\Model\ContractRenewalApplicationRequestStatusUpdater;
use App\WebService\Billing\ClientInterface;
use Disque\Queue\Job;
use Disque\Queue\Queue;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use League\Tactician\CommandBus;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class ContractRenewalApplicationRequestStatusUpdaterTest extends TestCase
{
    public function testProcessArrayDataFnWithoutContractAndApplicationRequestStatusAsCANCELLED()
    {
        $data['applicationRequest'] = [
            'applicationRequestNumber' => 'SWAP123456',
            'status' => 'COMPLETED',
        ];
        $data['contract'] = [
            'contractNumber' => 'SWCC123456',
        ];

        $contractRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn(null);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getId()->willReturn(123456);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::CANCELLED));
        $applicationRequestProphecy->setStatus(new ApplicationRequestStatus('COMPLETED'))->shouldBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $applicationRequestRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $applicationRequestRepositoryProphecy->findOneBy(['applicationRequestNumber' => 'SWAP123456'])->willReturn($applicationRequest);
        $applicationRequestRepository = $applicationRequestRepositoryProphecy->reveal();

        $expressionComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $expressionComparison = $expressionComparisonProphecy->reveal();

        $expressionAndXProphecy = $this->prophesize(Expr\Andx::class);
        $expressionAndX = $expressionAndXProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(ApplicationRequest::class)->willReturn($applicationRequestRepository);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->flush()->willReturn();
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($applicationRequest)->willReturn(123);
        $iriConverter = $iriConverterProphecy->reveal();

        $job = new Job([
            'data' => [
                'id' => 123456,
                'applicationRequest' => 123,
            ],
            'type' => JobType::APPLICATION_REQUEST_CANCELLED,
        ]);

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueueProphecy->push($job)->shouldBeCalled();
        $emailsQueue = $emailsQueueProphecy->reveal();

        $webServiceQueueProphecy = $this->prophesize(Queue::class);
        $webServiceQueueProphecy->push($job)->shouldBeCalled();
        $webServiceQueue = $webServiceQueueProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $billingClientProphecy = $this->prophesize(ClientInterface::class);
        $billingClient = $billingClientProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $expectedResult = [];

        $contractRenewalApplicationRequestStatusUpdater = new ContractRenewalApplicationRequestStatusUpdater($commandBus, '', $emailsQueue, $webServiceQueue, $entityManager, $iriConverter, $serializer, $billingClient, $logger, 'Asia/Singapore');
        $actualResult = $contractRenewalApplicationRequestStatusUpdater->processArrayData([$data]);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testQueueEmailJobFnWithApplicationRequestStatusAsCOMPLETED()
    {
        $data['applicationRequest'] = [
            'applicationRequestNumber' => 'SWAP123456',
            'status' => 'CANCELLED',
        ];
        $data['contract'] = [
            'contractNumber' => 'SWCC123456',
        ];

        $contractRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn(null);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getId()->willReturn(123456);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy->setStatus(new ApplicationRequestStatus('CANCELLED'))->shouldBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $applicationRequestRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $applicationRequestRepositoryProphecy->findOneBy(['applicationRequestNumber' => 'SWAP123456'])->willReturn($applicationRequest);
        $applicationRequestRepository = $applicationRequestRepositoryProphecy->reveal();

        $expressionComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $expressionComparison = $expressionComparisonProphecy->reveal();

        $expressionAndXProphecy = $this->prophesize(Expr\Andx::class);
        $expressionAndX = $expressionAndXProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(ApplicationRequest::class)->willReturn($applicationRequestRepository);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->flush()->willReturn();
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($applicationRequest)->willReturn(123);
        $iriConverter = $iriConverterProphecy->reveal();

        $job = new Job([
            'data' => [
                'id' => 123456,
                'applicationRequest' => 123,
            ],
            'type' => JobType::APPLICATION_REQUEST_COMPLETED,
        ]);

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueueProphecy->push($job)->shouldBeCalled();
        $emailsQueue = $emailsQueueProphecy->reveal();

        $webServiceQueueProphecy = $this->prophesize(Queue::class);
        $webServiceQueueProphecy->push($job)->shouldBeCalled();
        $webServiceQueue = $webServiceQueueProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $billingClientProphecy = $this->prophesize(ClientInterface::class);
        $billingClient = $billingClientProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $expectedResult = [];

        $contractRenewalApplicationRequestStatusUpdater = new ContractRenewalApplicationRequestStatusUpdater($commandBus, '', $emailsQueue, $webServiceQueue, $entityManager, $iriConverter, $serializer, $billingClient, $logger, 'Asia/Singapore');
        $actualResult = $contractRenewalApplicationRequestStatusUpdater->processArrayData([$data]);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testQueueEmailJobFnWithApplicationRequestStatusAsREJECTED()
    {
        $data['applicationRequest'] = [
            'applicationRequestNumber' => 'SWAP123456',
            'status' => 'CANCELLED',
        ];
        $data['contract'] = [
            'contractNumber' => 'SWCC123456',
        ];

        $contractRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $contractRepositoryProphecy->findOneBy(['contractNumber' => 'SWCC123456'])->willReturn(null);
        $contractRepository = $contractRepositoryProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->getId()->willReturn(123456);
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::REJECTED));
        $applicationRequestProphecy->setStatus(new ApplicationRequestStatus('CANCELLED'))->shouldBeCalled();
        $applicationRequest = $applicationRequestProphecy->reveal();

        $applicationRequestRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $applicationRequestRepositoryProphecy->findOneBy(['applicationRequestNumber' => 'SWAP123456'])->willReturn($applicationRequest);
        $applicationRequestRepository = $applicationRequestRepositoryProphecy->reveal();

        $expressionComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $expressionComparison = $expressionComparisonProphecy->reveal();

        $expressionAndXProphecy = $this->prophesize(Expr\Andx::class);
        $expressionAndX = $expressionAndXProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(ApplicationRequest::class)->willReturn($applicationRequestRepository);
        $entityManagerProphecy->getRepository(Contract::class)->willReturn($contractRepository);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->flush()->willReturn();
        $entityManager = $entityManagerProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($applicationRequest)->willReturn(123);
        $iriConverter = $iriConverterProphecy->reveal();

        $job = new Job([
            'data' => [
                'id' => 123456,
                'applicationRequest' => 123,
            ],
            'type' => JobType::APPLICATION_REQUEST_REJECTED,
        ]);

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueueProphecy->push($job)->shouldBeCalled();
        $emailsQueue = $emailsQueueProphecy->reveal();

        $webServiceQueueProphecy = $this->prophesize(Queue::class);
        $webServiceQueueProphecy->push($job)->shouldBeCalled();
        $webServiceQueue = $webServiceQueueProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $billingClientProphecy = $this->prophesize(ClientInterface::class);
        $billingClient = $billingClientProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $expectedResult = [];

        $contractRenewalApplicationRequestStatusUpdater = new ContractRenewalApplicationRequestStatusUpdater($commandBus, '', $emailsQueue, $webServiceQueue, $entityManager, $iriConverter, $serializer, $billingClient, $logger, 'Asia/Singapore');
        $actualResult = $contractRenewalApplicationRequestStatusUpdater->processArrayData([$data]);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testUpdateApplicationRequestFnWithoutContractAndWithoutApplicationRequestNumber()
    {
        $data['applicationRequest'] = [
            'status' => 'CANCELLED',
        ];
        $data['contract'] = [
            'contractNumber' => 'SWCC123456',
        ];

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $expressionComparisonProphecy = $this->prophesize(Expr\Comparison::class);
        $expressionComparison = $expressionComparisonProphecy->reveal();

        $expressionAndXProphecy = $this->prophesize(Expr\Andx::class);
        $expressionAndX = $expressionAndXProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverter = $iriConverterProphecy->reveal();

        $emailsQueueProphecy = $this->prophesize(Queue::class);
        $emailsQueue = $emailsQueueProphecy->reveal();

        $webServiceQueueProphecy = $this->prophesize(Queue::class);
        $webServiceQueue = $webServiceQueueProphecy->reveal();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializer = $serializerProphecy->reveal();

        $billingClientProphecy = $this->prophesize(ClientInterface::class);
        $billingClient = $billingClientProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $expectedResult = [
            [
                'applicationRequest' => [
                    'status' => 'CANCELLED',
                ],
                'contract' => [
                    'contractNumber' => 'SWCC123456',
                ],
            ],
        ];

        $contractRenewalApplicationRequestStatusUpdater = new ContractRenewalApplicationRequestStatusUpdater($commandBus, '', $emailsQueue, $webServiceQueue, $entityManager, $iriConverter, $serializer, $billingClient, $logger, 'Asia/Singapore');
        $actualResult = $contractRenewalApplicationRequestStatusUpdater->processArrayData([$data]);

        $this->assertEquals($expectedResult, $actualResult);
    }
}
