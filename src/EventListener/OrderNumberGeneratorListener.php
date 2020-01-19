<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\Order\CalculateTotalPoints;
use App\Domain\Command\Order\UpdateOrderNumber;
use App\Entity\Order;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Map;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class OrderNumberGeneratorListener
{
    use Traits\RunningNumberLockTrait;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var Map<Order, string>
     */
    private $initialStatuses;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        $this->initialStatuses = new Map();
        $this->commandBus = $commandBus;
        $this->setEntityManager($entityManager);
        $this->setLocked(false);
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $data = $request->attributes->get('data');

        if (!$data instanceof Order) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var Order $order */
        $order = $data;

        $this->initialStatuses->put($order, $order->getOrderStatus()->getValue());
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof Order)) {
            return;
        }

        /**
         * @var Order
         */
        $order = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        $initialStatus = $this->initialStatuses->get($order, null);
        if (null === $initialStatus || OrderStatus::DRAFT === $initialStatus && OrderStatus::DRAFT !== $order->getOrderStatus()->getValue()) {
            $this->startLockTransaction();
            $this->commandBus->handle(new UpdateOrderNumber($order));
        }

        $this->commandBus->handle(new CalculateTotalPoints($order));
    }

    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();
    }
}
