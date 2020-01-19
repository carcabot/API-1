<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Entity\OfferListItem;
use App\Entity\OfferSerialNumber;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\QuantitativeValue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrderItemApi
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function createOrderItems(array $orderItems, Order $order)
    {
        foreach ($orderItems as $orderItemData) {
            $this->createOrderItem($orderItemData, $order);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function createOrderItem(array $orderItemData, Order $order): OrderItem
    {
        $qb = $this->entityManager->getRepository(OfferListItem::class)->createQueryBuilder('oli');
        $expr = $qb->expr();

        $orderItemData = $orderItemData[0];
        $orderItem = new OrderItem();

        $offerListItems = $qb->leftJoin('oli.item', 'offer')
            ->where($expr->eq('offer.offerNumber', ':number'))
            ->setParameter('number', $orderItemData['product_id'])
            ->getQuery()
            ->getResult();

        if (\count($offerListItems) > 0) {
            $orderItem->setOfferListItem($offerListItems[0]);
        } else {
            throw new BadRequestHttpException('No offer list item found');
        }

        $orderItem->setOrderQuantity(new QuantitativeValue((string) $orderItemData['quantity'], null, null, null));
        $orderItem->setUnitPrice(clone $offerListItems[0]->getPriceSpecification());
        $orderItem->setOrder($order);

        if (\count($orderItemData['serial_number']) > 0) {
            foreach ($orderItemData['serial_number'] as $serialNumber) {
                $existSerialNumber = $this->entityManager->getRepository(OfferSerialNumber::class)->findOneBy(['serialNumber' => $serialNumber]);

                if (null !== $existSerialNumber) {
                    $orderItem->addSerialNumber($existSerialNumber);
                    $existSerialNumber->setOrderItem($orderItem);
                    $existSerialNumber->setDatePurchased($order->getOrderDate());

                    $existingOfferListItem = $existSerialNumber->getOfferListItem();
                    $existingOfferListItem->setInventoryLevel(new QuantitativeValue((string) ((int) $existingOfferListItem->getInventoryLevel()->getValue() - 1), null, null, null));

                    $this->entityManager->persist($existSerialNumber);
                }
            }
        }

        $this->entityManager->persist($orderItem);

        return $orderItem;
    }
}
