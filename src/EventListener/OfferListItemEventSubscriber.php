<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Domain\Command\OfferListItem\UpdateInventoryLevel;
use App\Entity\OfferListItem;
use League\Tactician\CommandBus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OfferListItemEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @param CommandBus $commandBus
     */
    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['updateInventoryLevel', EventPriorities::PRE_WRITE],
            ],
        ];
    }

    public function updateInventoryLevel(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof OfferListItem)) {
            return;
        }

        /** @var OfferListItem $offerListItem */
        $offerListItem = $controllerResult;

        if (\in_array($request->getMethod(), [
            Request::METHOD_DELETE,
        ], true)) {
            return;
        }

        $this->commandBus->handle(new UpdateInventoryLevel($offerListItem));
    }
}
