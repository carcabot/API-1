<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\ApplicationRequest;
use App\Entity\User;
use App\Enum\AuthorizationRole;
use App\Enum\CustomerRelationshipType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

final class ApplicationRequestExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Security
     */
    private $security;

    /**
     * @param EntityManagerInterface $entityManager
     * @param Security               $security
     */
    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $this->addWhere($queryBuilder, $resourceClass, []);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        $this->addWhere($queryBuilder, $resourceClass, $identifiers);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass, array $identifiers): void
    {
        if (ApplicationRequest::class !== $resourceClass || $this->security->isGranted(AuthorizationRole::ROLE_API_USER) || null === $user = $this->security->getUser()) {
            return;
        }

        if ($user instanceof User) {
            // if code reaches here, it means that user is not admin, can only see application requests where user is the acquirer, creator or owner
            $expr = $queryBuilder->expr();
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->leftJoin(\sprintf('%s.customer', $rootAlias), 'customer')
                ->leftJoin('customer.relationships', 'customerRelationships')
                ->leftJoin('customerRelationships.from', 'relationshipContactPerson', Join::WITH, 'customerRelationships.type IN (:relationships)')
                ->andWhere(
                    $expr->orX(
                        $expr->eq(\sprintf('%s.acquiredFrom', $rootAlias), ':customer'),
                        $expr->eq(\sprintf('%s.creator', $rootAlias), ':user'),
                        $expr->eq('relationshipContactPerson.id', ':customer'),
                        $expr->eq('customer.id', ':customer')
                    )
                )
                ->setParameter(':customer', $user->getCustomerAccount()->getId())
                ->setParameter(':relationships', [CustomerRelationshipType::CONTACT_PERSON, CustomerRelationshipType::PARTNER_CONTACT_PERSON])
                ->setParameter(':user', $user->getId());
        }
    }
}
