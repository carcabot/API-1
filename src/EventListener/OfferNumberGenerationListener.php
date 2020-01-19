<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\Offer\UpdateOfferNumber;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class OfferNumberGenerationListener
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

        if (!($controllerResult instanceof Offer)) {
            return;
        }

        /** @var Offer $offer */
        $offer = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        $this->startLockTransaction();
        $this->commandBus->handle(new UpdateOfferNumber($offer));
    }

    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();
    }
}
