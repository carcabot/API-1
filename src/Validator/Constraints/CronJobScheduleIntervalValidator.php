<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\CronJobSchedule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CronJobScheduleIntervalValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $object = $value;

        if (!$constraint instanceof CronJobScheduleInterval) {
            throw new UnexpectedTypeException($constraint, CronJobScheduleInterval::class);
        }

        if (!$object instanceof CronJobSchedule) {
            throw new UnexpectedTypeException($constraint, CronJobSchedule::class);
        }

        if (!empty($object->getIntervals()) && !$object->getIntervals() instanceof \DateInterval) {
            $this->context->buildViolation($constraint->cronJobScheduleIntervalNotValidFormat)
                ->atPath('intervals')
                ->addViolation();
        }
    }
}
