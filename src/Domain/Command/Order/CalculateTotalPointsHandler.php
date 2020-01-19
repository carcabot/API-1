<?php

declare(strict_types=1);

namespace App\Domain\Command\Order;

use App\Entity\OrderItem;
use App\Entity\PriceSpecification;
use Doctrine\ORM\EntityManagerInterface;

class CalculateTotalPointsHandler
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

    public function handle(CalculateTotalPoints $command): void
    {
        $order = $command->getOrder();

        /** @var OrderItem[] $orderItems */
        $orderItems = $order->getItems();

        $totalPrice = 0;
        foreach ($orderItems as $orderItem) {
            $price = (int) $orderItem->getUnitPrice()->getPrice();
            $quantity = null !== $orderItem->getOrderQuantity()->getValue() ? (int) $orderItem->getOrderQuantity()->getValue() : 1;
            $totalPrice += ($price * $quantity);
        }
        $priceSpecs = new PriceSpecification(null, null, (string) $totalPrice, null);

        $order->setTotalPrice($priceSpecs);
    }
}
