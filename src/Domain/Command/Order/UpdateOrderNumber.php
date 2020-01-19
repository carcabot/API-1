<?php

declare(strict_types=1);

namespace App\Domain\Command\Order;

use App\Entity\Order;

class UpdateOrderNumber
{
    /**
     * @var Order
     */
    private $order;

    /**
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Gets the order.
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }
}
