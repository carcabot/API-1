<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\ContractAction;
use App\Entity\CustomerAccount;
use App\Entity\QuantitativeValue;
use App\Entity\UpdateContractAction;
use App\Enum\ActionStatus;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ContractActionType;
use App\Enum\ContractStatus;
use App\Enum\CustomerAccountStatus;
use App\Model\ApplicationRequestAccountClosureStatusUpdater;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;

class ApplicationRequestAccountClosureStatusUpdaterTest extends TestCase
{
    public function testProcessArrayData()
    {
        $data = [];
        $data['applicationRequest']['applicationRequestNumber'] = '123';
        $data['applicationRequest']['status'] = 'COMPLETED';
        $data['contract']['contractNumber'] = '1';
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);

        $endDate = new \DateTime('2019-05-05');

        $contractAction = new UpdateContractAction();

        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);
        $customerAccountProphecy->setStatus(new CustomerAccountStatus(CustomerAccountStatus::INACTIVE))->shouldBeCalled();
        $customerAccountProphecy->getDefaultCreditsContract()->willReturn(null);

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getActions()->willReturn([]);
        $contractProphecy->getContractNumber()->willReturn('1');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy->setStatus(new ContractStatus(ContractStatus::INACTIVE))->shouldBeCalled();
        $contractProphecy->setContractNumber(null)->shouldBeCalled();
        $contractProphecy->setEndDate($endDate)->shouldBeCalled();
        $contractProphecy->addAction($contractAction)->shouldBeCalled();
        $contractProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $contractProphecy->getPointCreditsBalance()->willReturn(new QuantitativeValue('0'));
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy->setStatus(new ApplicationRequestStatus('COMPLETED'))->shouldBeCalled();
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy->getContract()->willReturn($contract);
        $applicationRequestProphecy->getPreferredEndDate()->willReturn($endDate);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $customerAccountProphecy->getContracts()->willReturn([$contract]);
        $customerAccount = $customerAccountProphecy->reveal();

        $oldContract = $contract;

        $contractAction->setActionStatus(new ActionStatus(ActionStatus::COMPLETED));
        $contractAction->setObject($oldContract);
        $contractAction->setResult($contract);
        $contractAction->setInstrument($applicationRequest);
        $contractAction->setType(new ContractActionType(ContractActionType::ACCOUNT_CLOSURE));

        $applicationRequestRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $applicationRequestRepositoryProphecy->findOneBy(['applicationRequestNumber' => '123'])->willReturn($applicationRequest);
        $applicationRequestRepository = $applicationRequestRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(ApplicationRequest::class)->willReturn($applicationRequestRepository);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->persist($contract)->shouldBeCalled();
        $entityManagerProphecy->persist($contractAction)->shouldBeCalled();
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $applicationRequestAccountClosureStatusUpdater = new ApplicationRequestAccountClosureStatusUpdater($commandBus, $entityManager);
        $actualOutput = $applicationRequestAccountClosureStatusUpdater->processArrayData([$data, ['asd' => 'asd']]);
        $expectedOutput = [['asd' => 'asd']];

        $this->assertEquals($actualOutput, $expectedOutput);
    }

    public function testProcessArrayDataInactive()
    {
        $data = [];
        $data['applicationRequest']['applicationRequestNumber'] = '123';
        $data['applicationRequest']['status'] = 'COMPLETED';
        $data['contract']['contractNumber'] = '1';
        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);

        $endDate = new \DateTime('2019-05-05');
        $customerAccountProphecy = $this->prophesize(CustomerAccount::class);

        $contractActionProphecy = $this->prophesize(ContractAction::class);
        $contractActionProphecy->getActionStatus()->willReturn(new ActionStatus(ActionStatus::COMPLETED));

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->getActions()->willReturn([$contractActionProphecy]);
        $contractProphecy->getContractNumber()->willReturn('1');
        $contractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::INACTIVE));
        $contractProphecy->setStatus(new ContractStatus(ContractStatus::INACTIVE))->shouldBeCalled();
        $contractProphecy->setEndDate($endDate)->shouldBeCalled();
        $contractProphecy->getCustomer()->willReturn($customerAccountProphecy);
        $contractProphecy->getPointCreditsBalance()->willReturn(new QuantitativeValue('0'));
        $contract = $contractProphecy->reveal();

        $customerAccountProphecy->getDefaultCreditsContract()->willReturn($contractProphecy);
        $customerAccountProphecy->setDefaultCreditsContract(null)->shouldBeCalled();

        $applicationRequestProphecy->setStatus(new ApplicationRequestStatus('COMPLETED'))->shouldBeCalled();
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy->getContract()->willReturn($contract);
        $applicationRequestProphecy->getPreferredEndDate()->willReturn($endDate);
        $applicationRequestProphecy->getId()->willReturn(1);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $contractActionProphecy->getInstrument()->willReturn($applicationRequest);
        $contractAction = $contractActionProphecy->reveal();

        $tempContractProphecy = $this->prophesize(Contract::class);
        $tempContractProphecy->getContractNumber()->willReturn('2');
        $tempContractProphecy->getStatus()->willReturn(new ContractStatus(ContractStatus::ACTIVE));
        $tempContract = $tempContractProphecy->reveal();

        $customerAccountProphecy->getContracts()->willReturn([$contract, $tempContract]);
        $customerAccount = $customerAccountProphecy->reveal();

        $applicationRequestRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $applicationRequestRepositoryProphecy->findOneBy(['applicationRequestNumber' => '123'])->willReturn($applicationRequest);
        $applicationRequestRepository = $applicationRequestRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(ApplicationRequest::class)->willReturn($applicationRequestRepository);
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->persist($contract)->shouldBeCalled();
        $entityManagerProphecy->persist($customerAccount)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBus = $commandBusProphecy->reveal();

        $applicationRequestAccountClosureStatusUpdater = new ApplicationRequestAccountClosureStatusUpdater($commandBus, $entityManager);
        $actualOutput = $applicationRequestAccountClosureStatusUpdater->processArrayData([$data, ['asd' => 'asd']]);
        $expectedOutput = [['asd' => 'asd']];

        $this->assertEquals($actualOutput, $expectedOutput);
    }
}
