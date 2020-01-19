<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\CreditsTransaction\UpdateCreditsTransactionNumber;
use App\Entity\CreditsTransaction;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class CreditsTransactionNumberGenerationListener
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

        if (!($controllerResult instanceof CreditsTransaction)) {
            return;
        }

        /**
         * @var CreditsTransaction
         */
        $creditsTransaction = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        $this->startLockTransaction();
        $this->commandBus->handle(new UpdateCreditsTransactionNumber($creditsTransaction));
    }

    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();
    }
}
