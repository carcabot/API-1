<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\CustomerAccount\UpdateAccountNumber;
use App\Domain\Command\CustomerAccount\UpdateReferralCode;
use App\Entity\CustomerAccount;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class CustomerAccountNumberGenerationListener
{
    use Traits\RunningNumberLockTrait;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        $this->commandBus = $commandBus;
        $this->setEntityManager($entityManager);
        $this->setLocked(false);
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof CustomerAccount)) {
            return;
        }

        /** @var CustomerAccount $customerAccount */
        $customerAccount = $controllerResult;

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        if (null !== $customerAccount->getAccountNumber()) {
            return;
        }

        $this->startLockTransaction();
        $this->commandBus->handle(new UpdateAccountNumber($customerAccount));
        $this->commandBus->handle(new UpdateReferralCode($customerAccount));
    }

    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();
    }
}
