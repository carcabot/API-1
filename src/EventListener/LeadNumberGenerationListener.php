<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\Lead\UpdateLeadNumber;
use App\Entity\Lead;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class LeadNumberGenerationListener
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

        if (!($controllerResult instanceof Lead)) {
            return;
        }

        /** @var Lead $lead */
        $lead = $controllerResult;

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        if (null !== $lead->getLeadNumber()) {
            return;
        }

        $this->startLockTransaction();
        $this->commandBus->handle(new UpdateLeadNumber($lead));
    }

    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();
    }
}
