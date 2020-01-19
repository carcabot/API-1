<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ApplicationRequestReferralCode extends Constraint
{
    public $applicationRequestReferralCodeNotAllowed = 'This referral code is yours.';
    public $applicationRequestReferralCodeNotExists = 'This referral code is not exist.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
