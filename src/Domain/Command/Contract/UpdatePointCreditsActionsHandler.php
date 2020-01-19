<?php

declare(strict_types=1);

namespace App\Domain\Command\Contract;

class UpdatePointCreditsActionsHandler
{
    public function handle(UpdatePointCreditsActions $command): void
    {
        $contract = $command->getContract();
        $creditsAction = $command->getCreditsAction();

        $contract->addPointCreditsAction($creditsAction);
    }
}
