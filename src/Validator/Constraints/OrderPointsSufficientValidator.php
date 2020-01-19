<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\Order;
use App\Entity\OrderItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class OrderPointsSufficientValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($protocol, Constraint $constraint)
    {
        $entity = null;
        $class = null;
        $object = $protocol;

        if (!$constraint instanceof OrderPointsSufficient) {
            throw new UnexpectedTypeException($constraint, OrderPointsSufficient::class);
        }

        if ($object instanceof Order) {
            $entity = Order::class;
        }

        if (null !== $object && null !== $entity) {
            /** @var OrderItem[] $orderItems */
            $orderItems = $object->getItems();

            $totalPrice = 0;
            foreach ($orderItems as $orderItem) {
                $price = (int) $orderItem->getUnitPrice()->getPrice();
                $quantity = null !== $orderItem->getOrderQuantity()->getValue() ? (int) $orderItem->getOrderQuantity()->getValue() : 1;
                $totalPrice += ($price * $quantity);
            }

            if ($totalPrice > (int) $object->getObject()->getPointCreditsBalance()->getValue()) {
                $this->context->buildViolation($constraint->orderPointsNotSufficient)
                    ->atPath('Order.totalPoints')
                    ->addViolation();
            }
        }
    }
}
