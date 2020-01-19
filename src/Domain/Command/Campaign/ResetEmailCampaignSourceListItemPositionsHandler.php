<?php

declare(strict_types=1);

namespace App\Domain\Command\Campaign;

use App\Entity\Campaign;
use Doctrine\ORM\EntityManagerInterface;

class ResetEmailCampaignSourceListItemPositionsHandler
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

    public function handle(ResetEmailCampaignSourceListItemPositions $command): void
    {
        $campaign = $command->getCampaign();

        $toUpdateCampaign = $this->entityManager->getRepository(Campaign::class)->find($campaign->getId());

        if (null !== $toUpdateCampaign) {
            $recipientLists = $toUpdateCampaign->getRecipientLists();

            $i = 0;
            foreach ($recipientLists as $recipientList) {
                $recipients = $recipientList->getItemListElement();
                foreach ($recipients as $recipient) {
                    $recipient->setPosition(++$i);
                }
            }

            $this->entityManager->flush();
        }
    }
}
