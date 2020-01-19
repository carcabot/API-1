<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\Order;

use App\Domain\Command\Order\UpdateOrderNumber;
use App\Domain\Command\Order\UpdateOrderNumberHandler;
use App\Entity\Order;
use App\Model\OrderNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdateOrderNumberTest extends TestCase
{
    public function testUpdateOrderNumber()
    {
        $length = 8;
        $prefix = 'O-';
        $type = 'order';
        $number = 1;

        $orderProphecy = $this->prophesize(Order::class);
        $orderProphecy->setOrderNumber('O-00000001')->shouldBeCalled();
        $order = $orderProphecy->reveal();

        $orderNumberGeneratorProphecy = $this->prophesize(OrderNumberGenerator::class);
        $orderNumberGeneratorProphecy->generate($order)->willReturn(\sprintf('%s%s', $prefix, \str_pad((string) $number, $length, '0', STR_PAD_LEFT)));
        $orderNumberGenerator = $orderNumberGeneratorProphecy->reveal();

        $updateOrderNumberHandler = new UpdateOrderNumberHandler($orderNumberGenerator);
        $updateOrderNumberHandler->handle(new UpdateOrderNumber($order));
    }
}
