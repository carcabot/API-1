<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 19/4/19
 * Time: 4:55 PM.
 */

namespace App\Tests\Model;

use App\Domain\Command\CreditsTransaction\UpdateCreditsTransactionNumber;
use App\Entity\MonetaryAmount;
use App\Entity\MoneyCreditsTransaction;
use App\Entity\Payment;
use App\Entity\PointCreditsTransaction;
use App\Entity\QuantitativeValue;
use App\Entity\UpdateCreditsAction;
use App\Entity\WithdrawCreditsAction;
use App\Model\CreditsTransactionProcessor;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use PHPUnit\Framework\TestCase;

class CreditsTransactionProcessorTest extends TestCase
{
    public function testCreditsTransactionProcessorWithCurrency()
    {
        $paymentProphecy = $this->prophesize(Payment::class);
        $payment = $paymentProphecy->reveal();

        $updateCreditsTransactionProphecy = $this->prophesize(WithdrawCreditsAction::class);
        $updateCreditsTransactionProphecy->getCurrency()->willReturn('testCurrency');
        $updateCreditsTransactionProphecy->getAmount()->willReturn('testAmount');
        $updateCreditsTransactionProphecy->getInstrument()->willReturn($payment);
        $updateCreditsTransaction = $updateCreditsTransactionProphecy->reveal();

        $moneyCreditsTransaction = new MoneyCreditsTransaction();
        $moneyCreditsTransaction->setAmount(new MonetaryAmount($updateCreditsTransaction->getAmount(), $updateCreditsTransaction->getCurrency()));
        $moneyCreditsTransaction->addPayment($updateCreditsTransaction->getInstrument());

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateCreditsTransactionNumber($moneyCreditsTransaction))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($moneyCreditsTransaction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedCreditsTransaction = new MoneyCreditsTransaction();
        $expectedCreditsTransaction->setAmount(new MonetaryAmount('testAmount', 'testCurrency'));
        $expectedCreditsTransaction->addPayment($payment);

        $creditsTransactionProcessor = new CreditsTransactionProcessor($commandBus, $entityManager);
        $actualCreditsTransaction = $creditsTransactionProcessor->createCreditsTransaction($updateCreditsTransaction);

        $this->assertEquals($expectedCreditsTransaction, $actualCreditsTransaction);
    }

    public function testCreditsTransactionProcessorWithoutCurrency()
    {
        $updateCreditsTransactionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsTransactionProphecy->getCurrency()->willReturn(null);
        $updateCreditsTransactionProphecy->getAmount()->willReturn('testAmount');
        $updateCreditsTransaction = $updateCreditsTransactionProphecy->reveal();

        $creditsTransaction = new PointCreditsTransaction();
        $creditsTransaction->setAmount(new QuantitativeValue($updateCreditsTransaction->getAmount()));

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateCreditsTransactionNumber($creditsTransaction))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($creditsTransaction)->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $expectedCreditsTransaction = new PointCreditsTransaction();
        $expectedCreditsTransaction->setAmount(new QuantitativeValue('testAmount'));

        $creditsTransactionProcessor = new CreditsTransactionProcessor($commandBus, $entityManager);
        $actualCreditsTransaction = $creditsTransactionProcessor->createCreditsTransaction($updateCreditsTransaction);

        $this->assertEquals($expectedCreditsTransaction, $actualCreditsTransaction);
    }
}
