<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Base as BaseProvider;
use libphonenumber\PhoneNumber;

class PhoneNumberRoleProvider extends BaseProvider
{
    /**
     * @var Generator
     */
    private $faker;

    public function generatePhoneNumber()
    {
        $faker = Factory::create('en_SG');

        $phoneNumber = new PhoneNumber();
        $phoneNumber->setNationalNumber($faker->phoneNumber);

        return $phoneNumber;
    }
}
