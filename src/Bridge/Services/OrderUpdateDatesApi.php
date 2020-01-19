<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\OldOrderIds;
use App\Document\RedemptionOrder;
use App\Entity\Order;
use App\Entity\RedeemCreditsAction;
use App\Entity\RunningNumber;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;

class OrderUpdateDatesApi
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
        if (null !== $existingOrder) {
            $contract = $existingOrder->getObject();

            /**
             * @vat UpdateCreditsAction
             */
            $actions = $contract->getPointCreditsActions();
            foreach ($actions as $action) {
                if ($action instanceof RedeemCreditsAction) {
                    if ($action->getInstrument()->getId() === $existingOrder->getId()) {
                        if (null !== $orderData->getCreatedAt()) {
                            $action->setDateCreated($orderData->getCreatedAt());
                            $action->setStartTime($orderData->getCreatedAt());
                        }

                        $this->entityManager->persist($action);
                        $this->entityManager->flush();
                        break;
                    }
                }
            }
        }
    }
}
