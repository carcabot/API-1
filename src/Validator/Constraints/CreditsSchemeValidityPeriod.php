<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CreditsSchemeValidityPeriod extends Constraint
{
    public $creditsSchemeOverlapPeriod = 'Cannot overlap';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
