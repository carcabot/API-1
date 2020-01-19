<?php

declare(strict_types=1);

namespace App\Domain\Command\Order;

use App\Entity\OfferSerialNumber;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AssignSerialNumbersHandler
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

    public function handle(AssignSerialNumbers $command): void
    {
        $order = $command->getOrder();

        /** @var OrderItem[] $orderItems */
        $orderItems = $order->getItems();
        $qb = $this->entityManager->getRepository(OfferSerialNumber::class)->createQueryBuilder('serialNumber');
        $expr = $qb->expr();

        foreach ($orderItems as $orderItem) {
            $offerListItem = $orderItem->getOfferListItem();

            $allSerialNumbers = $qb->select('count(serialNumber.id)')
                ->where($expr->eq('serialNumber.offerListItem', ':offerListItem'))
                ->setParameter('offerListItem', $offerListItem->getId())
                ->getQuery()
                ->getSingleScalarResult();

            if ($allSerialNumbers > 0) {
                $serialNumbers = $qb->select('serialNumber')
                    ->where($expr->eq('serialNumber.offerListItem', ':offerListItem'))
                    ->andWhere($expr->isNull('serialNumber.datePurchased'))
                    ->setParameter('offerListItem', $offerListItem->getId())
                    ->getQuery()
                    ->getResult();

                $count = $orderItem->getOrderQuantity()->getValue();

                if (null === $count) {
                    $count = 1;
                }

                if (\count($serialNumbers) < $count) {
                    throw new BadRequestHttpException('Not enough in stock.');
                }

                foreach ($serialNumbers as $serialNumber) {
                    if ($count < 1) {
                        break;
                    }

                    if (null === $serialNumber->getDatePurchased()) {
                        $serialNumber->setDatePurchased(new \DateTime());
                        $orderItem->addSerialNumber($serialNumber);

                        $this->entityManager->persist($serialNumber);
                        $this->entityManager->persist($orderItem);
                        --$count;
                    }
                }

                // if somehow not enough; paranoid coding at its finest
                if ($count > 0) {
                    throw new BadRequestHttpException('Not enough in stock.');
                }
            }
        }

        $this->entityManager->flush();
    }
}
