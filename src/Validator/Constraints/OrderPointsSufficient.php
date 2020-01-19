<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class OrderPointsSufficient extends Constraint
{
    public $orderPointsNotSufficient = 'Order points are greater than contract points';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
