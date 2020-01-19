<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Contract;
use App\Entity\CustomerAccountRelationship;
use App\Entity\User;
use App\Enum\AuthorizationRole;
use App\Enum\CustomerRelationshipType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

final class ContractExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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
        if (Contract::class !== $resourceClass || $this->security->isGranted(AuthorizationRole::ROLE_API_USER) || null === $user = $this->security->getUser()) {
            return;
        }

        if ($user instanceof User) {
            // if code reaches here, it means that user is not admin, can only see contracts where user is the owner
            $qb = $this->entityManager->getRepository(CustomerAccountRelationship::class)->createQueryBuilder('relationship');
            $expr = $qb->expr();

            $relationshipContracts = [];

            $relationships = $qb->select('relationship, contracts')
                ->join('relationship.contracts', 'contracts')
                ->where($expr->eq('relationship.from', ':customer'))
                ->andWhere($expr->in('relationship.type', ':relationships'))
                ->setParameter(':customer', $user->getCustomerAccount()->getId())
                ->setParameter(':relationships', [CustomerRelationshipType::CONTACT_PERSON, CustomerRelationshipType::PARTNER_CONTACT_PERSON])
                ->getQuery()
                ->getResult();

            foreach ($relationships as $relationship) {
                foreach ($relationship->getContracts() as $relationshipContract) {
                    $relationshipContracts[] = $relationshipContract->getId();

                    foreach ($relationshipContract->getActions() as $contractAction) {
                        $relationshipContracts[] = $contractAction->getObject()->getId();

                        if (null !== $contractAction->getResult()->getId()) {
                            $relationshipContracts[] = $contractAction->getResult()->getId();
                        }
                    }
                }
            }

            $relationshipContracts = \array_unique($relationshipContracts);

            $expr = $queryBuilder->expr();
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $orExpr = $expr->orX();

            $orExpr->add($expr->eq('customer.id', ':customer'));
            if (\count($relationshipContracts) > 0) {
                $orExpr->add(
                    $expr->in(\sprintf('%s.id', $rootAlias), $relationshipContracts)
                );
            }

            $queryBuilder->leftJoin(\sprintf('%s.customer', $rootAlias), 'customer')
                ->andWhere($orExpr)
                ->setParameter(':customer', $user->getCustomerAccount()->getId());
        }
    }
}
