<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\TariffDailyRate\ValidateTariffDailyRate;
use App\Entity\TariffDailyRate;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class TariffDailyRateGenerationListener
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
    public function onKernelViewPreWrite(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof TariffDailyRate)) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        /**
         * @var TariffDailyRate
         */
        $tariffDailyRate = $controllerResult;

        $this->commandBus->handle(new ValidateTariffDailyRate($tariffDailyRate));
    }
}
