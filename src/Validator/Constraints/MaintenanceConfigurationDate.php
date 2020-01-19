<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MaintenanceConfigurationDate extends Constraint
{
    public $maintenanceDateOverlap = 'There is a maintenance scheduled at this time, cannot overlap';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
