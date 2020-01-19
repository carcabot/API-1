<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\MaintenanceConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MaintenanceConfigurationDateValidator extends ConstraintValidator
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

        if (!$constraint instanceof MaintenanceConfigurationDate) {
            throw new UnexpectedTypeException($constraint, MaintenanceConfigurationDate::class);
        }

        if (!$object instanceof MaintenanceConfiguration) {
            throw new UnexpectedTypeException($constraint, MaintenanceConfiguration::class);
        }

        $queryParams = $usedInParams = [];

        $queryParams['start'] = $object->getPlannedStartDate();
        $queryParams['end'] = $object->getPlannedEndDate();
        $usedIns = $object->getUsedIn();

        foreach ($usedIns as $usedIn) {
            $used = \strtolower($usedIn);
            \array_push($usedInParams, "usedIn_{$used}");
            $queryParams["usedIn_{$used}"] = \json_encode($usedIn);
        }

        $qb = $this->entityManager->getRepository(MaintenanceConfiguration::class)->createQueryBuilder('maintenance');
        $expr = $qb->expr();

        $qb->where($expr->orX(
            $expr->andX($expr->lte('maintenance.plannedStartDate', ':start'), $expr->gte('maintenance.plannedEndDate', ':start')),
            $expr->andX($expr->lte('maintenance.plannedStartDate', ':end'), $expr->gte('maintenance.plannedEndDate', ':end'))
        ));

        $qb->andWhere($expr->in('maintenance.status', ':statuses'));
        $queryParams['statuses'] = ['PENDING', 'ACTIVE'];

        if (null !== $object->getId()) {
            $queryParams['id'] = $object->getId();
            $qb->andWhere($expr->neq('maintenance.id', ':id'));
        }

        if (\count($usedIns) > 0) {
            $expressions = [];

            foreach ($usedInParams as $usedInParam) {
                \array_push($expressions, $expr->eq(<<<"SQL"
                    jsonb_contains(CAST(maintenance.usedIn AS jsonb), :$usedInParam)
SQL
                    , $expr->literal(true)));
            }

            $orExpressions = $expr->orX();
            foreach ($expressions as $expression) {
                $orExpressions->add($expression);
            }
            $qb->andWhere($orExpressions);
        }

        $maintenanceConfiguration = $qb->setParameters($queryParams)
            ->getQuery()
            ->getResult();

        if (\count($maintenanceConfiguration) > 0) {
            $this->context->buildViolation($constraint->maintenanceDateOverlap)
                ->atPath('plannedStartDate')
                ->addViolation();

            $this->context->buildViolation($constraint->maintenanceDateOverlap)
                ->atPath('plannedEndDate')
                ->addViolation();
        }
    }
}
