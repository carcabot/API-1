<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION"})
 */
class UserUniqueUsernameOrEmail extends Constraint
{
    public $duplicateEmailMessage = 'This email is already used.';
    public $duplicateUsernameMessage = 'This username is already used.';
    public $noUsernameOrEmail = 'User must have an email or username.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
