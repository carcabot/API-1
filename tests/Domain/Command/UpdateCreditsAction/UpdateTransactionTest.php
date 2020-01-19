<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\UpdateCreditsAction;

use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Domain\Command\UpdateCreditsAction\UpdateTransactionHandler;
use App\Entity\MoneyCreditsTransaction;
use App\Entity\UpdateCreditsAction;
use App\Model\CreditsTransactionProcessor;
use PHPUnit\Framework\TestCase;

class UpdateTransactionTest extends TestCase
{
    public function testUpdateMoneyCreditsTransaction()
    {
        $moneyCreditsTransactionProphecy = $this->prophesize(MoneyCreditsTransaction::class);
        $updateCreditsActionProphecy = $this->prophesize(UpdateCreditsAction::class);
        $updateCreditsActionProphecy->setCreditsTransaction($moneyCreditsTransactionProphecy)->shouldBeCalled();
        $updateCreditsAction = $updateCreditsActionProphecy->reveal();
        $moneyCreditsTransaction = $moneyCreditsTransactionProphecy->reveal();

        $creditsTransactionProcessorProphecy = $this->prophesize(CreditsTransactionProcessor::class);
        $creditsTransactionProcessorProphecy->createCreditsTransaction($updateCreditsAction)->willReturn($moneyCreditsTransaction);
        $creditsTransactionProcessor = $creditsTransactionProcessorProphecy->reveal();

        $updateTransactionHandler = new UpdateTransactionHandler($creditsTransactionProcessor);
        $updateTransactionHandler->handle(new UpdateTransaction($updateCreditsAction));
    }
}
