<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\AdvisoryNotice;
use App\Entity\AffiliateProgram;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountRelationship;
use App\Entity\DigitalDocument;
use App\Entity\Merchant;
use App\Entity\Offer;
use App\Entity\OfferCatalog;
use App\Entity\OfferCategory;
use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\WebPage;
use App\Enum\AuthorizationRole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

final class DigitalDocumentExtension implements QueryItemExtensionInterface
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

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        $this->addWhere($queryBuilder, $resourceClass, $identifiers);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass, array $identifiers): void
    {
        if (DigitalDocument::class !== $resourceClass || $this->security->isGranted(AuthorizationRole::ROLE_API_USER) || null === $user = $this->security->getUser()) {
            return;
        }

        // in dire need of refactoring, or total revamp for security
        $expr = $this->entityManager->getExpressionBuilder();

        if (isset($identifiers['id']) && $user instanceof User) {
            // some arbitrary alias, not important
            $baseClassAlias = 'o';

            $fileAttributeClasses = [
                AdvisoryNotice::class,
            ];

            foreach ($fileAttributeClasses as $fileAttributeClass) {
                $matchingIds = $this->entityManager->createQueryBuilder()
                    ->select(\sprintf('%s.id', $baseClassAlias))
                    ->from($fileAttributeClass, $baseClassAlias)
                    ->join(\sprintf('%s.file', $baseClassAlias), 'file')
                    ->where($expr->eq('file.id', $expr->literal($identifiers['id'])))
                    ->getQuery()
                    ->getResult();

                if (\count($matchingIds) > 0) {
                    return;
                }
            }

            // this works because all of them are accessible by public, and also coverImage always has base image attribute
            $coverImageAttributeClasses = [
                AffiliateProgram::class,
                OfferCatalog::class,
                WebPage::class,
            ];

            foreach ($coverImageAttributeClasses as $coverImageAttributeClass) {
                $qb = $this->entityManager->createQueryBuilder()
                    ->select(\sprintf('%s.id', $baseClassAlias))
                    ->from($coverImageAttributeClass, $baseClassAlias)
                    ->leftJoin(\sprintf('%s.image', $baseClassAlias), 'coverImageAttributeClassImage')
                    ->leftJoin(\sprintf('%s.coverImage', $baseClassAlias), 'coverImageAttributeClassCoverImage');

                $orExpr = $this->getOrExpr($expr, ['coverImageAttributeClassImage', 'coverImageAttributeClassCoverImage'], $identifiers);

                if (!empty($orExpr->getParts())) {
                    $matchingIds = $qb->where($orExpr)->getQuery()->getResult();

                    if (\count($matchingIds) > 0) {
                        return;
                    }
                }
            }

            // those without cover image and accessible by public
            $imageAttributeClasses = [
                CustomerAccount::class,
                Merchant::class,
                Offer::class,
                OfferCategory::class,
            ];

            foreach ($imageAttributeClasses as $imageAttributeClass) {
                $qb = $this->entityManager->createQueryBuilder()
                    ->select(\sprintf('%s.id', $baseClassAlias))
                    ->from($imageAttributeClass, $baseClassAlias)
                    ->leftJoin(\sprintf('%s.image', $baseClassAlias), 'imageAttributeClassImage');

                $orExpr = $this->getOrExpr($expr, ['imageAttributeClassImage'], $identifiers);

                if (!empty($orExpr->getParts())) {
                    $matchingIds = $qb->where($orExpr)->getQuery()->getResult();

                    if (\count($matchingIds) > 0) {
                        return;
                    }
                }
            }

            $matchCreator = $this->entityManager->getRepository(DigitalDocument::class)->createQueryBuilder('digitalDocument')
                ->where($expr->eq('digitalDocument.creator', $expr->literal($user->getId())))
                ->andWhere($expr->eq('digitalDocument.id', ':id'))
                ->setParameter(':id', $identifiers['id'])
                ->getQuery()
                ->getResult();

            if (\count($matchCreator) > 0) {
                return;
            }

            $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('ca');
            $checkPermission = $qb->select('ca.id')
                ->leftJoin('ca.supplementaryFiles', 'cas')
                ->leftJoin(ApplicationRequest::class, 'a', Join::WITH, 'a.customer = ca.id OR a.acquiredFrom = ca.id')
                ->leftJoin('a.supplementaryFiles', 'asf')
                ->leftJoin('a.advisoryNotice', 'aan')
                ->leftJoin('aan.file', 'aanf')
                ->leftJoin(Contract::class, 'c', Join::WITH, 'c.customer = ca.id')
                ->leftJoin('c.supplementaryFiles', 'csf')
                ->leftJoin(Ticket::class, 't', Join::WITH, 't.customer = ca.id')
                ->leftJoin('t.supplementaryFiles', 'tsf')
                ->leftJoin(CustomerAccountRelationship::class, 'car', Join::WITH, 'car.from = ca.id')
                ->leftJoin('car.contracts', 'carc')
                ->leftJoin('carc.supplementaryFiles', 'carcf')
                ->where($expr->eq('ca.id', ':customer'))
                ->andWhere(
                    $expr->orX(
                        $expr->eq('cas.id', ':id'),
                        $expr->eq('asf.id', ':id'),
                        $expr->eq('aanf.id', ':id'),
                        $expr->eq('csf.id', ':id'),
                        $expr->eq('tsf.id', ':id'),
                        $expr->eq('carcf.id', ':id')
                    )
                )
                ->setParameter(':customer', $user->getCustomerAccount()->getId())
                ->setParameter(':id', $identifiers['id'])
                ->getQuery()
                ->getResult();

            if (\count($checkPermission) > 0) {
                return;
            }
        }

        // if code reaches here, it means that the document is private
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere($expr->eq(\sprintf('%s.id', $rootAlias), ':id'));
        $queryBuilder->setParameter('id', 0);
    }

    private function getOrExpr($expr, $aliases, $identifiers)
    {
        $orExpr = $expr->orX();

        foreach ($aliases as $alias) {
            $orExpr->add($expr->eq(\sprintf('%s.%s', $alias, 'id'), $expr->literal($identifiers['id'])));
        }

        return $orExpr;
    }
}
