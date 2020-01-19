<?php

declare(strict_types=1);

namespace App\Domain\Command\OfferListItem;

use App\Entity\OfferCatalog;
use App\Enum\CatalogStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DeleteOfferListItemHandler
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

    public function handle(DeleteOfferListItem $command)
    {
        $offerListItem = $command->getOfferListItem();
        $qb = $this->entityManager->getRepository(OfferCatalog::class)->createQueryBuilder('catalog');
        $expr = $qb->expr();

        $offerCatalogs = $qb->leftJoin('catalog.itemListElement', 'offerListItem')
            ->where($expr->eq('offerListItem.id', ':id'))
            ->setParameter('id', $offerListItem->getId())
            ->getQuery()
            ->getResult();

        foreach ($offerCatalogs as $offerCatalog) {
            if (CatalogStatus::ACTIVE === $offerCatalog->getStatus()->getValue() || CatalogStatus::INACTIVE === $offerCatalog->getStatus()->getValue()) {
                throw new BadRequestHttpException('Cannot delete');
            }
        }
    }
}
