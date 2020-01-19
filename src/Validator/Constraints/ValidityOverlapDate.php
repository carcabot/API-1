<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidityOverlapDate extends Constraint
{
    public $dateOverlap = 'Validity period cannot overlap';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
