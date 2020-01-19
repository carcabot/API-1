<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ApplicationRequestCustomerStatus extends Constraint
{
    public $customerBlacklisted = 'This customer has been blacklisted.';
    public $contactPersonBlacklisted = 'This contact person has been blacklisted.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
