<?php

declare(strict_types=1);

namespace App\Domain\Command\UpdateCreditsAction;

use App\Entity\RedeemCreditsAction;
use App\Enum\ActionStatus;
use Doctrine\ORM\EntityManagerInterface;

class CreateRedeemCreditsActionHandler
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

    public function handle(CreateRedeemCreditsAction $command): RedeemCreditsAction
    {
        $order = $command->getOrder();

        $redeemCreditsAction = new RedeemCreditsAction();
        $redeemCreditsAction->setObject($order->getObject());
        $redeemCreditsAction->setInstrument($order);
        $redeemCreditsAction->setAmount($order->getTotalPrice()->getPrice());
        $redeemCreditsAction->setStartTime(new \DateTime());
        $redeemCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

        $this->entityManager->persist($redeemCreditsAction);

        return $redeemCreditsAction;
    }
}
