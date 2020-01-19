<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\UpdateCreditsAction;

use App\Domain\Command\UpdateCreditsAction\CreateReciprocalAction;
use App\Domain\Command\UpdateCreditsAction\CreateReciprocalActionHandler;
use App\Entity\AllocateCreditsAction;
use App\Entity\Contract;
use App\Entity\CreditsTransaction;
use App\Entity\CustomerAccount;
use App\Entity\EarnContractCreditsAction;
use App\Entity\EarnCustomerCreditsAction;
use App\Entity\TransferCreditsAction;
use App\Enum\ActionStatus;
use PHPUnit\Framework\TestCase;

class CreateReciprocalActionTest extends TestCase
{
    public function testCreateFromTransferCreditsAction()
    {
        $now = new \DateTime();
        $amount = '100';
        $currency = null;
        $status = new ActionStatus(ActionStatus::COMPLETED);

        $recipientProphecy = $this->prophesize(CustomerAccount::class);
        $recipient = $recipientProphecy->reveal();

        $senderProphecy = $this->prophesize(CustomerAccount::class);
        $sender = $senderProphecy->reveal();

        $creditsTransactionProphecy = $this->prophesize(CreditsTransaction::class);
        $creditsTransaction = $creditsTransactionProphecy->reveal();

        $transferCreditsActionProphecy = $this->prophesize(TransferCreditsAction::class);
        $transferCreditsActionProphecy->getRecipient()->willReturn($recipient);
        $transferCreditsActionProphecy->getAmount()->willReturn($amount);
        $transferCreditsActionProphecy->getCurrency()->willReturn($currency);
        $transferCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransaction);
        $transferCreditsActionProphecy->getObject()->willReturn($sender);
        $transferCreditsActionProphecy->getStatus()->willReturn($status);
        $transferCreditsActionProphecy->getStartTime()->willReturn($now);
        $transferCreditsAction = $transferCreditsActionProphecy->reveal();

        $createReciprocalActionHandler = new CreateReciprocalActionHandler();
        $reciprocalAction = $createReciprocalActionHandler->handle(new CreateReciprocalAction($transferCreditsAction));

        $this->assertTrue($reciprocalAction instanceof EarnCustomerCreditsAction);
        // fix for phpstan??
        if ($reciprocalAction instanceof EarnCustomerCreditsAction) {
            $this->assertEquals($reciprocalAction->getObject(), $recipient);
            $this->assertEquals($reciprocalAction->getAmount(), $amount);
            $this->assertEquals($reciprocalAction->getCurrency(), $currency);
            $this->assertEquals($reciprocalAction->getCreditsTransaction(), $creditsTransaction);
            $this->assertEquals($reciprocalAction->getSender(), $sender);
            $this->assertEquals($reciprocalAction->getStatus(), $status);
            $this->assertEquals($reciprocalAction->getStartTime(), $now);
        }
    }

    public function testCreateFromAllocateCreditsAction()
    {
        $now = new \DateTime();
        $amount = '100';
        $currency = null;
        $status = new ActionStatus(ActionStatus::COMPLETED);

        $recipientProphecy = $this->prophesize(Contract::class);
        $recipient = $recipientProphecy->reveal();

        $senderProphecy = $this->prophesize(CustomerAccount::class);
        $sender = $senderProphecy->reveal();

        $creditsTransactionProphecy = $this->prophesize(CreditsTransaction::class);
        $creditsTransaction = $creditsTransactionProphecy->reveal();

        $allocateCreditsActionProphecy = $this->prophesize(AllocateCreditsAction::class);
        $allocateCreditsActionProphecy->getRecipient()->willReturn($recipient);
        $allocateCreditsActionProphecy->getAmount()->willReturn($amount);
        $allocateCreditsActionProphecy->getCurrency()->willReturn($currency);
        $allocateCreditsActionProphecy->getCreditsTransaction()->willReturn($creditsTransaction);
        $allocateCreditsActionProphecy->getObject()->willReturn($sender);
        $allocateCreditsActionProphecy->getStatus()->willReturn($status);
        $allocateCreditsActionProphecy->getStartTime()->willReturn($now);
        $allocateCreditsAction = $allocateCreditsActionProphecy->reveal();

        $createReciprocalActionHandler = new CreateReciprocalActionHandler();
        $reciprocalAction = $createReciprocalActionHandler->handle(new CreateReciprocalAction($allocateCreditsAction));

        $this->assertTrue($reciprocalAction instanceof EarnContractCreditsAction);
        // fix for phpstan??
        if ($reciprocalAction instanceof EarnContractCreditsAction) {
            $this->assertEquals($reciprocalAction->getObject(), $recipient);
            $this->assertEquals($reciprocalAction->getAmount(), $amount);
            $this->assertEquals($reciprocalAction->getCurrency(), $currency);
            $this->assertEquals($reciprocalAction->getCreditsTransaction(), $creditsTransaction);
            $this->assertEquals($reciprocalAction->getSender(), $sender);
            $this->assertEquals($reciprocalAction->getStatus(), $status);
            $this->assertEquals($reciprocalAction->getStartTime(), $now);
        }
    }
}
