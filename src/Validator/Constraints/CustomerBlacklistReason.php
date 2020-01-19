<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CustomerBlacklistReason extends Constraint
{
    public $customerBlacklistReasonRequired = 'Reason is required when adding to blacklist.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
