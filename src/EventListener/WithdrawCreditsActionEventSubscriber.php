<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Disque\JobType;
use App\Entity\UpdateCreditsAction;
use App\Entity\WithdrawCreditsAction;
use App\Enum\ContractStatus;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class WithdrawCreditsActionEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var DisqueQueue
     */
    private $webServicesQueue;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DisqueQueue            $webServicesQueue
     */
    public function __construct(EntityManagerInterface $entityManager, DisqueQueue $webServicesQueue)
    {
        $this->entityManager = $entityManager;
        $this->webServicesQueue = $webServicesQueue;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['onKernelView', EventPriorities::PRE_WRITE],
                ['postWithdrawCreditsActionWebService', EventPriorities::POST_WRITE],
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

        if (!$controllerResult instanceof UpdateCreditsAction) {
            return;
        }

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        /**
         * @var WithdrawCreditsAction
         */
        $withdrawCreditsAction = $controllerResult;

        if (!$withdrawCreditsAction instanceof WithdrawCreditsAction) {
            return;
        }

        if (null === $withdrawCreditsAction->getContract()) {
            $customer = $withdrawCreditsAction->getObject();

            $contract = $customer->getDefaultCreditsContract();
            if (null === $contract) {
                $contracts = $customer->getContracts();
                foreach ($contracts as $customerContract) {
                    if (ContractStatus::ACTIVE === $customerContract->getStatus()->getValue()) {
                        $contract = $customerContract;
                        break;
                    }
                }
            }

            $withdrawCreditsAction->setContract($contract);

            $this->entityManager->persist($withdrawCreditsAction);
        }
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function postWithdrawCreditsActionWebService(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$controllerResult instanceof UpdateCreditsAction) {
            return;
        }

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        /**
         * @var WithdrawCreditsAction
         */
        $withdrawCreditsAction = $controllerResult;

        if (!$withdrawCreditsAction instanceof WithdrawCreditsAction) {
            return;
        }

        $job = new DisqueJob([
            'data' => [
                'id' => $withdrawCreditsAction->getId(),
            ],
            'type' => JobType::WITHDRAW_CREDITS_ACTION_SUBMIT,
        ]);
        $this->webServicesQueue->push($job);
    }
}
