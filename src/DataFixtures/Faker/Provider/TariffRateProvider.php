<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Entity\TariffRate;
use App\Enum\ContractType;
use App\Enum\TariffRateStatus;
use App\Enum\TariffRateType;
use Faker\Provider\Base as BaseProvider;

class TariffRateProvider extends BaseProvider
{
    public function generateTariffRateStatus()
    {
        $status = self::randomElement(TariffRateStatus::toArray());

        return new TariffRateStatus($status);
    }

    public function generateTariffRateContractType()
    {
        return ContractType::toArray();
    }

    public function generateTariffRateType()
    {
        $type = self::randomElement(TariffRateType::toArray());

        return new TariffRateType($type);
    }

    public function generateDummyTariffRate()
    {
        return new TariffRate();
    }
}
