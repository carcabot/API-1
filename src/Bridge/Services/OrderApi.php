<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\OldOrderIds;
use App\Document\RedemptionOrder;
use App\Domain\Command\Contract\UpdatePointCreditsActions;
use App\Domain\Command\Order\CalculateTotalPoints;
use App\Domain\Command\UpdateCreditsAction\CreateRedeemCreditsAction;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\Contract;
use App\Entity\Order;
use App\Entity\RunningNumber;
use App\Enum\OrderStatus;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrderApi
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderItemApi
     */
    private $orderItemApi;

    /**
     * @param CommandBus             $commandBus
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param OrderItemApi           $orderItemApi
     */
    public function __construct(CommandBus $commandBus, DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger, OrderItemApi $orderItemApi)
    {
        $this->commandBus = $commandBus;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->orderItemApi = $orderItemApi;
    }

    /**
     * Create order ids into database.
     *
     * @param OldOrderIds $orderId
     */
    public function createIds(OldOrderIds $orderId)
    {
        $runningNumber = new RunningNumber();

        if (!empty($orderId->getNextNumber()) && null !== $orderId->getNextNumber()) {
            $runningNumber->setNumber($orderId->getNextNumber() - 1);
        }

        if (!empty($orderId->getDatePrefix()) && false !== $orderId->getDatePrefix()) {
            $runningNumber->setSeries('ym');
        }
        $runningNumber->setType('order');

        $this->entityManager->persist($runningNumber);
        $this->entityManager->flush();
    }

    public function createOrders(array $orders)
    {
        foreach ($orders as $orderData) {
            $this->createOrder($orderData);
            $this->entityManager->flush();
        }

        $this->entityManager->clear();
    }

    public function createOrder(RedemptionOrder $orderData)
    {
        $existingOrder = $this->entityManager->getRepository(Order::class)->findOneBy(['orderNumber' => $orderData->getOrderNumber()]);
        $order = new Order();

        if (null !== $existingOrder) {
            $order = $existingOrder;
        }

        $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $orderData->getContract()]);

        if (null !== $contract) {
            $order->setObject($contract);
        } else {
            throw new BadRequestHttpException('No contract found for order: '.$orderData->getOrderNumber());
        }

        $customer = $contract->getCustomer();
        $order->setCustomer($customer);

        if (null !== $orderData->getOrderNumber()) {
            $order->setOrderNumber($orderData->getOrderNumber());
        }

        if (null !== $orderData->getCreatedAt()) {
            $order->setOrderDate($orderData->getCreatedAt());
        }

        $order->setOrderStatus(new OrderStatus(OrderStatus::DELIVERED));

        foreach ($orderData->getItems() as $orderItemData) {
            $orderItem = $this->orderItemApi->createOrderItem([$orderItemData], $order);
            $order->addItems($orderItem);
        }

        $this->commandBus->handle(new CalculateTotalPoints($order));

        $this->entityManager->persist($order);

        $redeemCreditAction = $this->commandBus->handle(new CreateRedeemCreditsAction($order));
        $this->commandBus->handle(new UpdateTransaction($redeemCreditAction));
        $this->commandBus->handle(new UpdatePointCreditsActions($order->getObject(), $redeemCreditAction));

        $this->entityManager->flush();
    }
}
