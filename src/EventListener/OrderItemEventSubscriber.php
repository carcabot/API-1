<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\CustomerBlacklistConfiguration;
use App\Entity\OrderItem;
use App\Enum\BlacklistConfigurationAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class OrderItemEventSubscriber implements EventSubscriberInterface
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

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['onKernelView', EventPriorities::PRE_WRITE + 1],
            ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$controllerResult instanceof OrderItem) {
            return;
        }

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        /** @var OrderItem $orderItem */
        $orderItem = $controllerResult;

        $redemptionConfiguration = $this->entityManager->getRepository(CustomerBlacklistConfiguration::class)->findOneBy(['action' => new BlacklistConfigurationAction(BlacklistConfigurationAction::REDEMPTION)]);

        if (null !== $redemptionConfiguration) {
            if (true === $redemptionConfiguration->isEnabled()) {
                $this->checkBlacklistStatus($orderItem);
            }
        }
    }

    private function checkBlacklistStatus(OrderItem $orderItem)
    {
        $offerType = $orderItem->getOfferListItem()->getItem()->getType()->getValue();

        if ('BILL_REBATE' === $offerType) {
            $billRebateConfiguration = $this->entityManager->getRepository(CustomerBlacklistConfiguration::class)->findOneBy(['action' => new BlacklistConfigurationAction(BlacklistConfigurationAction::BILL_REBATE_REDEMPTION)]);

            if (null !== $billRebateConfiguration) {
                if (true !== $billRebateConfiguration->isEnabled()) {
                    return;
                }
            }
        }

        $order = $orderItem->getOrder();
        $customer = $order->getCustomer();

        if (null !== $customer->getDateBlacklisted() && $customer->getDateBlacklisted() <= new \DateTime('now', new \DateTimeZone('UTC'))) {
            // check customer status
            $this->entityManager->remove($order);
            $this->entityManager->flush();
            throw new BadRequestHttpException('This Customer has been blacklisted');
        }
    }
}
