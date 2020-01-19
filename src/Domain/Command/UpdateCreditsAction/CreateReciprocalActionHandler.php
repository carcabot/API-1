<?php

declare(strict_types=1);

namespace App\Domain\Command\UpdateCreditsAction;

use App\Entity\AllocateCreditsAction;
use App\Entity\EarnContractCreditsAction;
use App\Entity\EarnCustomerCreditsAction;
use App\Entity\TransferCreditsAction;
use App\Entity\UpdateCreditsAction;

class CreateReciprocalActionHandler
{
    public function handle(CreateReciprocalAction $command): ?UpdateCreditsAction
    {
        $updateCreditsAction = $command->getUpdateCreditsAction();
        $reciprocalAction = null;

        if ($updateCreditsAction instanceof TransferCreditsAction) {
            $reciprocalAction = new EarnCustomerCreditsAction();
            $reciprocalAction->setObject($updateCreditsAction->getRecipient());
            $reciprocalAction->setSender($updateCreditsAction->getObject());
        } elseif ($updateCreditsAction instanceof AllocateCreditsAction) {
            $reciprocalAction = new EarnContractCreditsAction();
            $reciprocalAction->setObject($updateCreditsAction->getRecipient());
            $reciprocalAction->setSender($updateCreditsAction->getObject());
        }

        if (null !== $reciprocalAction) {
            $reciprocalAction->setAmount($updateCreditsAction->getAmount());
            $reciprocalAction->setCreditsTransaction($updateCreditsAction->getCreditsTransaction());
            $reciprocalAction->setCurrency($updateCreditsAction->getCurrency());
            $reciprocalAction->setStartTime($updateCreditsAction->getStartTime());
            $reciprocalAction->setStatus($updateCreditsAction->getStatus());
        }

        return $reciprocalAction;
    }
}
