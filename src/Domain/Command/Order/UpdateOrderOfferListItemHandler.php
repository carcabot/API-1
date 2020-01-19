<?php

declare(strict_types=1);

namespace App\Domain\Command\Order;

use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;

class UpdateOrderOfferListItemHandler
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

    public function handle(UpdateOrderOfferListItem $command): void
    {
        $order = $command->getOrder();

        /** @var OrderItem[] $orderItems */
        $orderItems = $order->getItems();

        foreach ($orderItems as $orderItem) {
            $offerListItem = clone $orderItem->getOfferListItem();
            $offerListItem->setIsBasedOn($orderItem->getOfferListItem());

            $this->entityManager->persist($offerListItem);
            $orderItem->setOfferListItem($offerListItem);
        }
    }
}
