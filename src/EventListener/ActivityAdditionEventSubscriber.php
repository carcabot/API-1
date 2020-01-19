<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\ApplicationRequest;
use App\Entity\CustomerAccount;
use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ActivityAdditionEventSubscriber implements EventSubscriberInterface
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
                ['onPreWrite', EventPriorities::PRE_WRITE + 1],
            ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onPreWrite(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof ApplicationRequest) && !($controllerResult instanceof Ticket)) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        if (null !== $controllerResult->getCustomer()) {
            $activities = [];
            foreach ($controllerResult->getActivities() as $activity) {
                $activities[$activity->getId()] = $activity;
            }

            $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');
            $customerActivities = $qb->select('customer.id as customer_id, activity.id as activity_id')
                ->join('customer.activities', 'activity')
                ->where($qb->expr()->in('activity.id', ':activities'))
                ->setParameter('activities', $activities)
                ->getQuery()
                ->getResult();

            if (\count($customerActivities) !== \count($activities)) {
                foreach ($customerActivities as $customerActivity) {
                    if (isset($activities[$customerActivity['activity_id']])) {
                        unset($activities[$customerActivity['activity_id']]);
                    }
                }
            }

            if (\count($activities) > 0) {
                foreach ($activities as $activity) {
                    // @todo figure sht out
                    $controllerResult->getCustomer()->removeActivity($activity);
                    $controllerResult->getCustomer()->addActivity($activity);
                }

                $this->entityManager->persist($controllerResult->getCustomer());
            }
        }
    }
}
