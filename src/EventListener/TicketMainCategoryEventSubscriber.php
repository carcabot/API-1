<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Domain\Command\TicketCategory\UpdateTicketTypeForChildren;
use App\Domain\Command\TicketCategory\UpdateTicketTypeFromParent;
use App\Entity\TicketCategory;
use League\Tactician\CommandBus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TicketMainCategoryEventSubscriber implements EventSubscriberInterface
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
                ['updateTicketTypes', EventPriorities::PRE_WRITE + 1],
            ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function updateTicketTypes(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof TicketCategory)) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_PUT,
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        /**
         * @var TicketCategory
         */
        $ticketCategory = $controllerResult;

        $this->commandBus->handle(new UpdateTicketTypeFromParent($ticketCategory));
        $this->commandBus->handle(new UpdateTicketTypeForChildren($ticketCategory));
    }
}
