<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Activity;
use App\Entity\ApplicationRequest;
use App\Entity\CustomerAccount;
use App\Entity\EmailActivity;
use App\Entity\Lead;
use App\Entity\PhoneContactActivity;
use App\Entity\Quotation;
use App\Entity\SmsActivity;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\AuthorizationRole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

final class ActivityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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
        $activityClasses = [
            Activity::class,
            EmailActivity::class,
            PhoneContactActivity::class,
            SmsActivity::class,
        ];

        if (!\in_array($resourceClass, $activityClasses, true) || $this->security->isGranted(AuthorizationRole::ROLE_API_USER) || null === $user = $this->security->getUser()) {
            return;
        }

        if ($user instanceof User) {
            // if code reaches here, it means that user is not admin, can only see activities where user is the acquirer, creator or owner
            $expr = $queryBuilder->expr();
            $rootAlias = $queryBuilder->getRootAliases()[0];

            $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('c');
            $activityIds = $qb->select('c.id as baseId', 'ca.id as caId', 'aa.id as aaId', 'la.id as laId', 'qa.id as qaId', 'ta.id as taId')
                ->join('c.user', 'u')
                ->leftJoin('c.activities', 'ca')
                ->leftJoin(ApplicationRequest::class, 'a', Join::WITH, 'a.customer = ca.id OR a.acquiredFrom = ca.id OR a.creator = u.id')
                ->leftJoin('a.activities', 'aa')
                ->leftJoin(Lead::class, 'l', Join::WITH, 'l.creator = u.id')
                ->leftJoin('l.activities', 'la')
                ->leftJoin(Quotation::class, 'q', Join::WITH, 'q.creator = u.id')
                ->leftJoin('q.activities', 'qa')
                ->leftJoin(Ticket::class, 't', Join::WITH, 't.customer = ca.id OR t.creator = u.id')
                ->leftJoin('t.activities', 'ta')
                ->where($expr->eq('ca.id', ':customer'))
                ->setParameter(':customer', $user->getCustomerAccount()->getId())
                ->getQuery()
                ->getResult();

            $allowedIds = $this->getLeftJoinIdsSelect($activityIds, ['baseId']);

            $queryBuilder->andWhere($expr->in(\sprintf('%s.id', $rootAlias), ':id'));
            $queryBuilder->setParameter('id', $allowedIds);
        }
    }

    private function getLeftJoinIdsSelect(array $totalMatchingIds, array $nonIdKeys = []): array
    {
        $ids = [];
        $x = 0;
        foreach ($totalMatchingIds as $matchingIds) {
            foreach ($nonIdKeys as $nonIdKey) {
                unset($matchingIds[$nonIdKey]);
            }

            foreach ($matchingIds as $matchingId) {
                if (null === $matchingId) {
                    continue;
                }
                $ids[$matchingId] = ++$x;
            }
        }
        $ids = \array_values(\array_flip($ids));

        return $ids;
    }
}
