<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\OfferListItem;
use App\Entity\OfferSerialNumber;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class OrderInventoryLevelValidator extends ConstraintValidator
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
        $entity = null;
        $class = null;
        $object = $protocol;

        if (!$constraint instanceof OrderInventoryLevel) {
            throw new UnexpectedTypeException($constraint, OrderInventoryLevel::class);
        }

        if ($object instanceof Order) {
            $entity = Order::class;
        }

        if (null !== $object && null !== $entity) {
            /**
             * @var OrderItem[]
             */
            $orderItems = $object->getItems();
            $errors = [];

            foreach ($orderItems as $key => $orderItem) {
                /**
                 * @var OfferListItem
                 */
                $offerListItem = $orderItem->getOfferListItem();
                if (null !== $offerListItem->getInventoryLevel()->getValue()) {
                    if ((int) $offerListItem->getInventoryLevel()->getValue() < (int) $orderItem->getOrderQuantity()->getValue()) {
                        $errors[$key] = true;
                    }
                } else {
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

                        if ((int) $unusedSerialNumbers < (int) $orderItem->getOrderQuantity()->getValue()) {
                            $errors[$key] = true;
                        }
                    }
                }
            }

            foreach ($errors as $key => $error) {
                $this->context->buildViolation($constraint->inventoryLevelNotEnough)
                    ->atPath('items['.$key.'].orderQuantity.value')
                    ->addViolation();
            }
        }
    }
}
