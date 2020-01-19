<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Enum\AccountType;
use App\Enum\DwellingType;
use App\Enum\LeadStatus;
use App\Enum\MeterType;
use Faker\Provider\Base as BaseProvider;

class LeadProvider extends BaseProvider
{
    public function generateLeadStatus()
    {
        $status = self::randomElement(LeadStatus::toArray());

        return new LeadStatus($status);
    }

    public function generateLeadType()
    {
        $type = self::randomElement(AccountType::toArray());

        return new AccountType($type);
    }

    public function generateDwellingType()
    {
        $type = self::randomElement(DwellingType::toArray());

        return new DwellingType($type);
    }

    public function generateMeterType()
    {
        $type = self::randomElement(MeterType::toArray());

        return new MeterType($type);
    }
}
