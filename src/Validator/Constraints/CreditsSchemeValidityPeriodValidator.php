<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\CreditsScheme;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CreditsSchemeValidityPeriodValidator extends ConstraintValidator
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
        $creditsScheme = null;

        if (!$constraint instanceof CreditsSchemeValidityPeriod) {
            throw new UnexpectedTypeException($constraint, CreditsSchemeValidityPeriod::class);
        }

        if (!$object instanceof CreditsScheme) {
            throw new UnexpectedTypeException($constraint, CreditsScheme::class);
        }

        $validFrom = $object->getValidFrom();
        $validThrough = $object->getValidThrough();

        $qb = $this->entityManager->getRepository(CreditsScheme::class)->createQueryBuilder('crdts');
        $expr = $qb->expr();

        $qb->where($expr->andX(
            $expr->orX(
                $expr->andX($expr->lte('crdts.validFrom', ':from'), $expr->gte('crdts.validThrough', ':from')),
                $expr->andX($expr->lte('crdts.validFrom', ':to'), $expr->gte('crdts.validThrough', ':to'))),
            $expr->eq('crdts.schemeId', ':id'),
            $expr->isNull('crdts.isBasedOn')))
            ->setParameter('from', $validFrom)
            ->setParameter('to', $validThrough)
            ->setParameter('id', $object->getSchemeId());

        if (null !== $object->getId()) {
            $qb->andWhere($expr->neq('crdts.id', ':oid'))
                ->setParameter('oid', $object->getId());
        }

        $creditsSchemes = $qb->getQuery()->getResult();

        if (\count($creditsSchemes) > 0) {
            $this->context->buildViolation($constraint->creditsSchemeOverlapPeriod)
                ->atPath('validFrom')
                ->addViolation();

            $this->context->buildViolation($constraint->creditsSchemeOverlapPeriod)
                ->atPath('validThrough')
                ->addViolation();

            $this->context->buildViolation($constraint->creditsSchemeOverlapPeriod)
                ->atPath('schemeId')
                ->addViolation();
        }
    }
}
