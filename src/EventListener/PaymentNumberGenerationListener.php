<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\Payment\UpdatePaymentNumber;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class PaymentNumberGenerationListener
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

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof Payment)) {
            return;
        }

        /** @var Payment $payment */
        $payment = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        $this->startLockTransaction();
        $this->commandBus->handle(new UpdatePaymentNumber($payment));
    }

    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();
    }
}
