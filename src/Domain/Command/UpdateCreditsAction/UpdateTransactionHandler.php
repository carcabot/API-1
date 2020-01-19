<?php

declare(strict_types=1);

namespace App\Domain\Command\UpdateCreditsAction;

use App\Model\CreditsTransactionProcessor;

class UpdateTransactionHandler
{
    /**
     * @var CreditsTransactionProcessor
     */
    private $creditsTransactionProcessor;

    /**
     * @param CreditsTransactionProcessor $creditsTransactionProcessor
     */
    public function __construct(CreditsTransactionProcessor $creditsTransactionProcessor)
    {
        $this->creditsTransactionProcessor = $creditsTransactionProcessor;
    }

    public function handle(UpdateTransaction $command): void
    {
        $updateCreditsAction = $command->getUpdateCreditsAction();
        $creditsTransaction = $this->creditsTransactionProcessor->createCreditsTransaction($updateCreditsAction);

        $updateCreditsAction->setCreditsTransaction($creditsTransaction);
    }
}
