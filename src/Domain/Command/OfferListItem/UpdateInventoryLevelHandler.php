<?php

declare(strict_types=1);

namespace App\Domain\Command\OfferListItem;

use App\Entity\OfferSerialNumber;
use App\Entity\QuantitativeValue;
use Doctrine\ORM\EntityManagerInterface;

class UpdateInventoryLevelHandler
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

    public function handle(UpdateInventoryLevel $command)
    {
        // Removed some code in favour of not saving the inventory level at all. Should be done via normalizer but this will do for now.
        $offerListItem = $command->getOfferListItem();
        $qb = $this->entityManager->getRepository(OfferSerialNumber::class)->createQueryBuilder('serialNumber');
        $expr = $qb->expr();

        $allSerialNumbers = $qb->select('count(serialNumber.id)')
            ->where($expr->eq('serialNumber.offerListItem', ':offerListItem'))
            ->setParameter('offerListItem', $offerListItem->getId())
            ->getQuery()
            ->getSingleScalarResult();

        if ($allSerialNumbers > 0) {
            $unusedSerialNumbers = $qb->select('count(serialNumber.id)')
                ->where($expr->eq('serialNumber.offerListItem', ':offerListItem'))
                ->andWhere($expr->isNull('serialNumber.datePurchased'))
                ->setParameter('offerListItem', $offerListItem->getId())
                ->getQuery()
                ->getSingleScalarResult();

            $offerListItem->setInventoryLevel(new QuantitativeValue((string) $unusedSerialNumbers));
        }
    }
}
