<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Model\OrderNumberGenerator;
use App\Model\RunningNumberGenerator;
use PHPUnit\Framework\TestCase;

class OrderNumberGeneratorTest extends TestCase
{
    public function testGenerateOrderNumber()
    {
        $length = 9;
        $prefix = 'O';
        $type = 'order';

        $orderProphecy = $this->prophesize(Order::class);
        $orderProphecy->getOrderStatus()->willReturn(new OrderStatus(OrderStatus::PAYMENT_DUE));
        $order = $orderProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $orderNumberGenerator = new OrderNumberGenerator($runningNumberGenerator, [], 'UTC');
        $orderNumber = $orderNumberGenerator->generate($order);

        $this->assertEquals($orderNumber, 'O000000001');
    }

    public function testGenerateDraftOrderNumber()
    {
        $length = 5;
        $prefix = 'ODFT';
        $type = 'order';

        $orderProphecy = $this->prophesize(Order::class);
        $orderProphecy->getOrderStatus()->willReturn(new OrderStatus(OrderStatus::DRAFT));
        $order = $orderProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $orderNumberGenerator = new OrderNumberGenerator($runningNumberGenerator, [], 'UTC');
        $orderNumber = $orderNumberGenerator->generate($order);

        $this->assertEquals($orderNumber, 'ODFT00001');
    }
}
