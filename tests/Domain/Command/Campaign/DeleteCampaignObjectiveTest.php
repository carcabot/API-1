<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\Campaign;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Domain\Command\Campaign\DeleteCampaignObjective;
use App\Domain\Command\Campaign\DeleteCampaignObjectiveHandler;
use App\Entity\Campaign;
use App\Entity\CampaignObjective;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DeleteCampaignObjectiveTest extends TestCase
{
    public function testCannotDeleteCampaignObjectiveInUse()
    {
        $campaignObjectiveProphecy = $this->prophesize(CampaignObjective::class);
        $campaignObjective = $campaignObjectiveProphecy->reveal();

        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaign = $campaignProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($campaignObjective)->willReturn('/campaign_objectives/1');
        $iriConverter = $iriConverterProphecy->reveal();

        $campaignRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $campaignRepositoryProphecy->findBy(['objective' => $iriConverter->getIriFromItem($campaignObjective)])->willReturn($campaign);
        $campaignRepository = $campaignRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Campaign::class)->willReturn($campaignRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('This Campaign Objective is in use, unable to delete');

        $deleteCampaignObjectiveHandler = new DeleteCampaignObjectiveHandler($entityManager, $iriConverter);
        $deleteCampaignObjectiveHandler->handle(new DeleteCampaignObjective($campaignObjective));
    }

    public function testCanDeleteCampaignObjectiveNotInUse()
    {
        $campaignObjectiveProphecy = $this->prophesize(CampaignObjective::class);
        $campaignObjective = $campaignObjectiveProphecy->reveal();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($campaignObjective)->willReturn('/campaign_objectives/1000');
        $iriConverter = $iriConverterProphecy->reveal();

        $campaignRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $campaignRepositoryProphecy->findBy(['objective' => $iriConverter->getIriFromItem($campaignObjective)])->willReturn(null);
        $campaignRepository = $campaignRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(Campaign::class)->willReturn($campaignRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $deleteCampaignObjectiveHandler = new DeleteCampaignObjectiveHandler($entityManager, $iriConverter);
        $deleteCampaignObjectiveHandler->handle(new DeleteCampaignObjective($campaignObjective));

        $this->addToAssertionCount(1);
    }
}
