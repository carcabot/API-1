<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
class UserTwoFactorAuthenticationSetup extends Constraint
{
    public $noRecipientSpecified = 'Please, Specify a Recipient';
    public $wrongRecipientType = 'Please, check the recipient supplied';
    public $invalidNumber = 'Please Supply a valid mobile number';
    public $invalidEmail = 'Please Supply a valid Email Address';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
