<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

class UpdateMoneyCreditsActionsHandler
{
    public function handle(UpdateMoneyCreditsActions $command): void
    {
        $customerAccount = $command->getCustomerAccount();
        $creditsAction = $command->getCreditsAction();

        $customerAccount->addMoneyCreditsAction($creditsAction);
    }
}
