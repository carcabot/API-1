<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\AdvisoryNotice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidityOverlapDateValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($protocol, Constraint $constraint)
    {
        $object = $protocol;

        if (!$constraint instanceof ValidityOverlapDate) {
            throw new UnexpectedTypeException($constraint, ValidityOverlapDate::class);
        }

        if (!$object instanceof AdvisoryNotice) {
            throw new UnexpectedTypeException($constraint, AdvisoryNotice::class);
        }

        $qb = $this->entityManager->getRepository(AdvisoryNotice::class)->createQueryBuilder('advisoryNotice');
        $expr = $qb->expr();

        if (null !== $object->getValidFrom() && null !== $object->getValidThrough()) {
            $qb->where(
                $expr->andX(
                    $expr->gte('advisoryNotice.validThrough', $expr->literal($object->getValidFrom()->format('c'))),
                    $expr->lte('advisoryNotice.validFrom', $expr->literal($object->getValidThrough()->format('c')))
                )
            );

            if (null !== $object->getId()) {
                $qb->andWhere($expr->neq('advisoryNotice.id', $expr->literal($object->getId())));
            }

            $advisoryNotices = $qb->getQuery()->getResult();

            if (\count($advisoryNotices) > 0) {
                $this->context->buildViolation($constraint->dateOverlap)
                    ->atPath('validFrom')
                    ->addViolation();

                $this->context->buildViolation($constraint->dateOverlap)
                    ->atPath('validThrough')
                    ->addViolation();
            }
        }
    }
}
