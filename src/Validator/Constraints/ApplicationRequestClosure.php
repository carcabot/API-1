<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ApplicationRequestClosure extends Constraint
{
    public $applicationRequestClosureRequiredField = 'This value is required.';
    public $applicationRequestClosureExistingField = 'There is an existing application request for the Contract';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
