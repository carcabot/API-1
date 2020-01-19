<?php

declare(strict_types=1);

namespace App\Domain\Command\Campaign;

use App\Entity\CampaignExpectationListItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DeleteCampaignExpectationHandler
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(DeleteCampaignExpectation $command)
    {
        $campaignExpectation = $command->getCampaignExpectation();
        $campaignExpectations = $this->entityManager->getRepository(CampaignExpectationListItem::class)->findBy(['item' => $campaignExpectation]);

        if (!empty($campaignExpectations)) {
            throw new BadRequestHttpException('This Campaign Expectation is in use, unable to delete');
        }
    }
}
