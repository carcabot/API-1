<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UserUniqueUsernameOrEmailValidator extends ConstraintValidator
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
        if (!$constraint instanceof UserUniqueUsernameOrEmail) {
            throw new UnexpectedTypeException($constraint, UserUniqueUsernameOrEmail::class);
        }

        if (!$protocol instanceof User) {
            throw new UnexpectedTypeException($protocol, User::class);
        }

        /** @var User $user */
        $user = $protocol;

        $qb = $this->entityManager->createQueryBuilder();
        $expr = $this->entityManager->getExpressionBuilder();

        if (null !== $user->getUsername()) {
            $qb->select('user')
                ->from(User::class, 'user')
                ->where($expr->eq('user.username', $expr->literal($user->getUsername())));

            if (null !== $user->getId()) {
                $qb->andWhere($expr->neq('user.id', $expr->literal($user->getId())));
            }
            $existingUsernameUser = $qb->getQuery()->getOneOrNullResult();

            if (null !== $existingUsernameUser) {
                $this->context->buildViolation($constraint->duplicateUsernameMessage)
                    ->setParameter('{{ value }}', $this->formatValue($user->getUsername()))
                    ->atPath('username')
                    ->addViolation();
            }
        } elseif (null !== $user->getEmail()) {
            $qb->select('user')
                ->from(User::class, 'user')
                ->where($expr->eq('user.email', $expr->literal(\strtolower($user->getEmail()))));

            if (null !== $user->getId()) {
                $qb->andWhere($expr->neq('user.id', $expr->literal($user->getId())));
            }
            $existingEmailUser = $qb->getQuery()->getOneOrNullResult();

            if (null !== $existingEmailUser) {
                $this->context->buildViolation($constraint->duplicateEmailMessage)
                    ->setParameter('{{ value }}', $this->formatValue($user->getEmail()))
                    ->atPath('email')
                    ->addViolation();
            }
        } else {
            $this->context->buildViolation($constraint->noUsernameOrEmail)
                ->addViolation();
        }
    }
}
