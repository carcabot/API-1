<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Enum\CommissionCategory;
use App\Enum\CommissionType;
use Faker\Provider\Base as BaseProvider;

class CommissionRateProvider extends BaseProvider
{
    public function generateCommissionCategory()
    {
        $industry = self::randomElement(CommissionCategory::toArray());

        return new CommissionCategory($industry);
    }

    public function generateCommissionType()
    {
        $industry = self::randomElement(CommissionType::toArray());

        return new CommissionType($industry);
    }
}
