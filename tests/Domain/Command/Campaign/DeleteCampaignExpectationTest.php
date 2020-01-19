<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\Campaign;

use App\Domain\Command\Campaign\DeleteCampaignExpectation;
use App\Domain\Command\Campaign\DeleteCampaignExpectationHandler;
use App\Entity\CampaignExpectation;
use App\Entity\CampaignExpectationListItem;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DeleteCampaignExpectationTest extends TestCase
{
    public function testCannotDeleteCampaignExpectationInUse()
    {
        $campaignExpectationProphecy = $this->prophesize(CampaignExpectation::class);
        $campaignExpectation = $campaignExpectationProphecy->reveal();

        $campaignExpectationRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $campaignExpectationRepositoryProphecy->findBy(['item' => $campaignExpectation])->willReturn([$campaignExpectation, $campaignExpectation]);
        $campaignExpectationRepository = $campaignExpectationRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(CampaignExpectationListItem::class)->willReturn($campaignExpectationRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('This Campaign Expectation is in use, unable to delete');

        $deleteCampaignObjectiveHandler = new DeleteCampaignExpectationHandler($entityManager);
        $deleteCampaignObjectiveHandler->handle(new DeleteCampaignExpectation($campaignExpectation));
    }

    public function testCanDeleteCampaignObjectiveNotInUse()
    {
        $campaignExpectationProphecy = $this->prophesize(CampaignExpectation::class);
        $campaignExpectation = $campaignExpectationProphecy->reveal();

        $campaignExpectationRepositoryProphecy = $this->prophesize(EntityRepository::class);
        $campaignExpectationRepositoryProphecy->findBy(['item' => $campaignExpectation])->willReturn(null);
        $campaignExpectationRepository = $campaignExpectationRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(CampaignExpectationListItem::class)->willReturn($campaignExpectationRepository);
        $entityManager = $entityManagerProphecy->reveal();

        $deleteCampaignObjectiveHandler = new DeleteCampaignExpectationHandler($entityManager);
        $deleteCampaignObjectiveHandler->handle(new DeleteCampaignExpectation($campaignExpectation));

        $this->addToAssertionCount(1);
    }
}
