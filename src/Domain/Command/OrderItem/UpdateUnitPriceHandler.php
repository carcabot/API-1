<?php

declare(strict_types=1);

namespace App\Domain\Command\OrderItem;

class UpdateUnitPriceHandler
{
    public function handle(UpdateUnitPrice $command): void
    {
        $orderItem = $command->getOrderItem();

        $orderItem->setUnitPrice(clone $orderItem->getOfferListItem()->getPriceSpecification());
    }
}
