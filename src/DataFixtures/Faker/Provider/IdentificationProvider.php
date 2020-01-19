<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Enum\IdentificationName;
use Faker\Provider\Base as BaseProvider;

class IdentificationProvider extends BaseProvider
{
    public function generateIdentificationName()
    {
        $name = new IdentificationName(IdentificationName::UNIQUE_ENTITY_NUMBER);

        return $name;
    }

    public function generateIdentificationValue()
    {
        $value = self::randomDigit();

        return $value;
    }
}
