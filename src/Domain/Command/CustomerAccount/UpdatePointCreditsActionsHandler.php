<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

class UpdatePointCreditsActionsHandler
{
    public function handle(UpdatePointCreditsActions $command): void
    {
        $customerAccount = $command->getCustomerAccount();
        $creditsAction = $command->getCreditsAction();

        $customerAccount->addPointCreditsAction($creditsAction);
    }
}
