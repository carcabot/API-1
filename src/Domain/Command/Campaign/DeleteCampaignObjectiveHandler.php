<?php

declare(strict_types=1);

namespace App\Domain\Command\Campaign;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\Campaign;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DeleteCampaignObjectiveHandler
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     */
    public function __construct(EntityManagerInterface $entityManager, IriConverterInterface $iriConverter)
    {
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
    }

    public function handle(DeleteCampaignObjective $command)
    {
        $campaignObjective = $command->getCampaignObjective();
        $campaigns = $this->entityManager->getRepository(Campaign::class)->findBy(['objective' => $this->iriConverter->getIriFromItem($campaignObjective)]);

        if (!empty($campaigns)) {
            throw new BadRequestHttpException('This Campaign Objective is in use, unable to delete');
        }
    }
}
