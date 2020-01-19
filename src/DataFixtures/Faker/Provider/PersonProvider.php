<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Enum\GenderType;
use App\Enum\MaritalStatus;
use Faker\Provider\Base as BaseProvider;

class PersonProvider extends BaseProvider
{
    public function generatePersonGender()
    {
        $gender = self::randomElement(GenderType::toArray());

        return new GenderType($gender);
    }

    public function generateMaritalStatus()
    {
        $status = self::randomElement(MaritalStatus::toArray());

        return new MaritalStatus($status);
    }
}
