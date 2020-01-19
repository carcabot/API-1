<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\UpdateCreditsAction;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class UpdateCreditsActionCreationListener
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

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof UpdateCreditsAction)) {
            return;
        }

        /**
         * @var UpdateCreditsAction
         */
        $updateCreditsAction = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        $this->commandBus->handle(new UpdateTransaction($updateCreditsAction));
    }
}
