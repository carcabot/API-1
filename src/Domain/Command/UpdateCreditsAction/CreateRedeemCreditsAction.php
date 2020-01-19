<?php

declare(strict_types=1);

namespace App\Domain\Command\UpdateCreditsAction;

use App\Entity\Order;

class CreateRedeemCreditsAction
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

    public function getOrder(): Order
    {
        return $this->order;
    }
}
