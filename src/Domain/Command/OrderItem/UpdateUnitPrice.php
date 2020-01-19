<?php

declare(strict_types=1);

namespace App\Domain\Command\OrderItem;

use App\Entity\OrderItem;

class UpdateUnitPrice
{
    /**
     * @var OrderItem
     */
    private $orderItem;

    /**
     * @param OrderItem $orderItem
     */
    public function __construct(OrderItem $orderItem)
    {
        $this->orderItem = $orderItem;
    }

    /**
     * Gets the orderItem.
     *
     * @return OrderItem
     */
    public function getOrderItem(): OrderItem
    {
        return $this->orderItem;
    }
}
