<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Enum\AccountType;
use App\Enum\BillSubscriptionType;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use Faker\Provider\Base as BaseProvider;

class ContractProvider extends BaseProvider
{
    public function generateBillSubscriptionTypes()
    {
        $type = self::randomElement(BillSubscriptionType::toArray());

        return new BillSubscriptionType($type);
    }

    public function generateContractType()
    {
        $type = self::randomElement(ContractType::toArray());

        return new ContractType($type);
    }

    public function generateAccountType()
    {
        $type = self::randomElement(AccountType::toArray());

        return new AccountType($type);
    }

    public function generateContractStatus()
    {
        $status = self::randomElement(ContractStatus::toArray());

        return new ContractStatus($status);
    }
}
