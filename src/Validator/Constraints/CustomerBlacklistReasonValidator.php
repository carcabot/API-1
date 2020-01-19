<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\CustomerBlacklist;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CustomerBlacklistReasonValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($protocol, Constraint $constraint)
    {
        $object = $protocol;
        $creditsScheme = null;

        if (!$constraint instanceof CustomerBlacklistReason) {
            throw new UnexpectedTypeException($constraint, CustomerBlacklistReason::class);
        }

        if (!$object instanceof CustomerBlacklist) {
            throw new UnexpectedTypeException($constraint, CustomerBlacklist::class);
        }

        if ('ADD' === $object->getAction() && (
            null === $object->getReason() || '' === \trim($object->getReason())
        )) {
            $this->context->buildViolation($constraint->customerBlacklistReasonRequired)
                ->atPath('reason')
                ->addViolation();
        }
    }
}
