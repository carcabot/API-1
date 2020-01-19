<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\Ticket\UpdateTicketNumber;
use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class TicketNumberGenerationListener
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

        if (!($controllerResult instanceof Ticket)) {
            return;
        }

        /** @var Ticket $ticket */
        $ticket = $controllerResult;

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        $this->startLockTransaction();
        $this->commandBus->handle(new UpdateTicketNumber($ticket));
    }

    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();
    }
}
