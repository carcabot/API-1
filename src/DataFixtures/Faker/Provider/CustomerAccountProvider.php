<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Entity\CustomerAccount;
use App\Enum\AccountCategory;
use App\Enum\AccountType;
use App\Enum\CustomerAccountStatus;
use Faker\Provider\Base as BaseProvider;

class CustomerAccountProvider extends BaseProvider
{
    public function generateCustomerAccountCategory()
    {
        $category = self::randomElement(AccountCategory::toArray());

        return [new AccountCategory($category)];
    }

    public function generateCustomerAccountStatus()
    {
        $status = self::randomElement(CustomerAccountStatus::toArray());

        return new CustomerAccountStatus($status);
    }

    public function generateCustomerAccountType()
    {
        $type = self::randomElement(AccountType::toArray());

        return new AccountType($type);
    }

    public function generateDummyCustomerAccount()
    {
        return new CustomerAccount();
    }
}
