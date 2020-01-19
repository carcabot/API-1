<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ApplicationRequestRenewal extends Constraint
{
    public $applicationRequestRenewalRequiredField = 'This value is required.';
    public $applicationRequestRenewalExistingField = 'There is an existing application request for the Contract';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
