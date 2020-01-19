<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CronJobScheduleInterval extends Constraint
{
    public $cronJobScheduleIntervalNotValidFormat = 'This is not a valid format';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
