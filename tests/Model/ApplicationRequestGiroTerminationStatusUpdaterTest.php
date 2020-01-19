<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Enum\ApplicationRequestStatus;
use App\Model\ApplicationRequestGiroTerminationStatusUpdater;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ApplicationRequestGiroTerminationStatusUpdaterTest extends TestCase
{
    public function testProcessArrayData()
    {
        $data = [];
        $data['applicationRequest']['status'] = 'COMPLETED';
        $data['applicationRequest']['applicationRequestNumber'] = '123';

        $contractProphecy = $this->prophesize(Contract::class);
        $contractProphecy->setGiroOption(false)->shouldBeCalled();
        $contract = $contractProphecy->reveal();

        $applicationRequestProphecy = $this->prophesize(ApplicationRequest::class);
        $applicationRequestProphecy->setStatus(new ApplicationRequestStatus('COMPLETED'))->shouldBeCalled();
        $applicationRequestProphecy->getStatus()->willReturn(new ApplicationRequestStatus(ApplicationRequestStatus::COMPLETED));
        $applicationRequestProphecy->getContract()->willReturn($contract);
        $applicationRequest = $applicationRequestProphecy->reveal();

        $applicationRequestRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $applicationRequestRepositoryProphecy->findOneBy(['applicationRequestNumber' => '123'])->willReturn($applicationRequest);
        $applicationRequestRepository = $applicationRequestRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(ApplicationRequest::class)->willReturn($applicationRequestRepository);
        $entityManagerProphecy->persist($contract)->shouldBeCalled();
        $entityManagerProphecy->persist($applicationRequest)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $applicationRequestGiroTerminationStatusUpdater = new ApplicationRequestGiroTerminationStatusUpdater($entityManager);
        $applicationRequestGiroTerminationStatusUpdater->processArrayData([$data]);
    }
}
