<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\User;
use App\Enum\AccountCategory;
use App\Enum\AuthorizationRole;
use App\Service\AuthenticationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

final class UserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AuthenticationHelper
     */
    private $authenticationHelper;

    /**
     * @param EntityManagerInterface $entityManager
     * @param AuthenticationHelper   $authenticationHelper
     */
    public function __construct(EntityManagerInterface $entityManager, AuthenticationHelper $authenticationHelper)
    {
        $this->entityManager = $entityManager;
        $this->authenticationHelper = $authenticationHelper;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        // if ROLE_API_USER we allow, otherwise there should not be any other fetches to /users
        $this->addWhere($queryBuilder, $resourceClass, []);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        $this->addWhere($queryBuilder, $resourceClass, $identifiers);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass, array $identifiers): void
    {
        if (User::class !== $resourceClass || $this->authenticationHelper->hasRole(AuthorizationRole::ROLE_API_USER) || null === $user = $this->authenticationHelper->getAuthenticatedUser()) {
            return;
        }

        if (isset($identifiers['id']) && $user instanceof User && (string) $user->getId() === (string) $identifiers['id']) {
            return;
        }

        if (isset($identifiers['id']) && $user instanceof User) {
            $userCheck = $this->entityManager->getRepository(User::class)->find($identifiers['id']);

            // harmless to allow FETCH for ROLE_HOMEPAGE
            if (null !== $userCheck && \in_array(AuthorizationRole::ROLE_HOMEPAGE, $userCheck->getRoles(), true)) {
                return;
            }

            $corporation = $user->getCustomerAccount()->getCorporationDetails();

            if (null !== $corporation) {
                foreach ($corporation->getEmployees() as $employeeRole) {
                    if (null !== $employeeRole->getEmployee()->getUser() && $employeeRole->getEmployee()->getUser()->getId() === $identifiers['id']) {
                        return;
                    }
                }
            }
        }

        if ($this->authenticationHelper->hasRole(AuthorizationRole::ROLE_HOMEPAGE) ||
            (
                null !== $this->authenticationHelper->getImpersonatorUser() &&
                \in_array(AuthorizationRole::ROLE_HOMEPAGE, $this->authenticationHelper->getImpersonatorUser()->getRoles(), true)
            )
        ) {
            $expr = $queryBuilder->expr();

            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->join(\sprintf('%s.customerAccount', $rootAlias), 'userCustomerAccount')
                ->andWhere(
                    $expr->orX(
                        $expr->andX(
                            $expr->eq(\sprintf(<<<'SQL'
                                jsonb_contains(CAST(%s.%s AS jsonb), :%s)
SQL
                            , 'userCustomerAccount', 'categories', 'salesRep'),
                            $expr->literal(true))
                        ),
                        $expr->andX(
                            $expr->eq(\sprintf(<<<'SQL'
                                jsonb_contains(CAST(%s.%s AS jsonb), :%s)
SQL
                            , 'userCustomerAccount', 'categories', 'partner'),
                            $expr->literal(true))
                        )
                    )
                );

            $queryBuilder->setParameter(':salesRep', \json_encode(AccountCategory::SALES_REPRESENTATIVE));
            $queryBuilder->setParameter(':partner', \json_encode(AccountCategory::PARTNER));

            return;
        }

        if (isset($identifiers['id']) && $user instanceof User && \in_array(AccountCategory::SALES_REPRESENTATIVE, $user->getCustomerAccount()->getCategories(), true)) {
            $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');
            $expr = $qb->expr();

            $subQuery = $qb->select('u.id')
                ->distinct()
                ->join('u.customerAccount', 'ca')
                ->join('ca.corporationDetails', 'corp')
                ->join('corp.employees', 'emp')
                ->where($expr->eq('emp.employee', $expr->literal($user->getCustomerAccount()->getId())))
                ->getDQL();

            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->andWhere($expr->in(\sprintf('%s.id', $rootAlias), $subQuery));

            return;
        }

        // if code reaches here, it means that it is restricted
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere($queryBuilder->expr()->eq(\sprintf('%s.id', $rootAlias), ':id'));
        $queryBuilder->setParameter('id', 0);
    }
}
