<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ApplicationRequestTransferOut extends Constraint
{
    public $applicationRequestTransferOutRequiredField = 'This value is required.';
    public $applicationRequestTransferOutExistingField = 'There is an existing application request for the Contract';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
