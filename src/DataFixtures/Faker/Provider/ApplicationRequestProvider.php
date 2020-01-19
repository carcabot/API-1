<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\Enum\MeterType;
use Faker\Provider\Base as BaseProvider;

class ApplicationRequestProvider extends BaseProvider
{
    public function generateApplicationRequestStatus()
    {
        $status = self::randomElement(ApplicationRequestStatus::toArray());

        return new ApplicationRequestStatus($status);
    }

    public function generateApplicationRequestType()
    {
        $type = self::randomElement(ApplicationRequestType::toArray());

        return new ApplicationRequestType($type);
    }

    public function generateMeterType()
    {
        $type = self::randomElement(MeterType::toArray());

        return new MeterType($type);
    }

    public function generateSelfReadOption(MeterType $meterType)
    {
        if (MeterType::AMI === $meterType->getValue()) {
            return false;
        }

        return true;
    }
}
