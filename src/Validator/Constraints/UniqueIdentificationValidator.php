<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\CustomerAccount;
use App\Entity\Lead;
use App\Enum\AccountType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueIdentificationValidator extends ConstraintValidator
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
        $class = null;
        $object = $protocol;

        $qb = $this->entityManager->createQueryBuilder();
        $expr = $this->entityManager->getExpressionBuilder();

        if (!$constraint instanceof UniqueIdentification) {
            throw new UnexpectedTypeException($constraint, UniqueIdentification::class);
        }

        if ($protocol instanceof Lead) {
            $class = Lead::class;
        }

        if ($protocol instanceof CustomerAccount) {
            $class = CustomerAccount::class;
        }

        $baseJoinAlias = null;
        $now = new \DateTime();
        $identifiers = [];

        if (null !== $class && null !== $object) {
            if (
                null !== $object->getType() &&
                AccountType::INDIVIDUAL === $object->getType()->getValue() &&
                null !== $object->getPersonDetails() &&
                0 !== \count($object->getPersonDetails()->getIdentifiers())
            ) {
                $identifiers = $object->getPersonDetails()->getIdentifiers();
                $baseJoinAlias = 'personDetails';
            }

            if (
                null !== $object->getType() &&
                AccountType::CORPORATE === $object->getType()->getValue() &&
                null !== $object->getCorporationDetails() &&
                0 !== \count($object->getCorporationDetails()->getIdentifiers())
            ) {
                $identifiers = $object->getCorporationDetails()->getIdentifiers();
                $baseJoinAlias = 'corporationDetails';
            }

            if (\count($identifiers) > 0 && null !== $baseJoinAlias) {
                $qb->select('entity')
                    ->from($class, 'entity')
                    ->join('entity.'.$baseJoinAlias, $baseJoinAlias)
                    ->join($baseJoinAlias.'.identifiers', 'identifier')
                    ->where($expr->andX(
                        $expr->orX(
                            $expr->isNull('identifier.validFrom'),
                            $expr->lte('identifier.validFrom', ':now')
                        ),
                        $expr->orX(
                            $expr->isNull('identifier.validThrough'),
                            $expr->gte('identifier.validThrough', ':now')
                        )
                    ))
                    ->andWhere($expr->eq('identifier.name', ':identifierName'))
                    ->andWhere($expr->eq('identifier.value', ':identifierValue'));

                if (null !== $object->getId()) {
                    $qb->andWhere($expr->neq('entity.id', $expr->literal($object->getId())));
                }

                foreach ($identifiers as $i => $identifier) {
                    if ($identifier->isValid()) {
                        $existingEntities = $qb->setParameter('identifierName', $identifier->getName())
                            ->setParameter('identifierValue', $identifier->getValue())
                            ->setParameter('now', $now->format('c'))
                            ->getQuery()
                            ->getResult();

                        if (\count($existingEntities) > 0) {
                            $this->context->buildViolation($constraint->duplicateIdentification)
                                ->setParameter('{{ value }}', $this->formatValue($identifier->getValue()))
                                ->atPath("{$baseJoinAlias}.identifiers[{$i}].value")
                                ->addViolation();
                        }
                    }
                }
            }
        }
    }
}
