<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Entity\CustomerAccount;
use App\Enum\AccountCategory;

class AddCustomerCategoriesHandler
{
    public function handle(AddCustomerCategories $command): ?CustomerAccount
    {
        $customerAccount = $command->getCustomerAccount();

        if (!\in_array(AccountCategory::CUSTOMER, $customerAccount->getCategories(), true)) {
            $customerAccount->addCategory(AccountCategory::CUSTOMER);

            return $customerAccount;
        }

        return null;
    }
}
