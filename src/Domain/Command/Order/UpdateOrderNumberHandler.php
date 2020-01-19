<?php

declare(strict_types=1);

namespace App\Domain\Command\Order;

use App\Model\OrderNumberGenerator;

class UpdateOrderNumberHandler
{
    /**
     * @var OrderNumberGenerator
     */
    private $orderNumberGenerator;

    /**
     * @param OrderNumberGenerator $orderNumberGenerator
     */
    public function __construct(OrderNumberGenerator $orderNumberGenerator)
    {
        $this->orderNumberGenerator = $orderNumberGenerator;
    }

    public function handle(UpdateOrderNumber $command): void
    {
        $order = $command->getOrder();
        $orderNumber = $this->orderNumberGenerator->generate($order);

        $order->setOrderNumber($orderNumber);
    }
}
