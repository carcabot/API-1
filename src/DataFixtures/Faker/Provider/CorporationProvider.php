<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Enum\Industry;
use Faker\Provider\Base as BaseProvider;

class CorporationProvider extends BaseProvider
{
    public function generateIndustry()
    {
        $industry = self::randomElement(Industry::toArray());

        return new Industry($industry);
    }
}
