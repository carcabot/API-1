<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\Campaign\DeleteCampaignExpectation;
use App\Entity\CampaignExpectation;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class CampaignExpectationDeletionEventListener
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

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof CampaignExpectation)) {
            return;
        }

        $campaignExpectation = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_DELETE,
        ], true)) {
            return;
        }

        $this->commandBus->handle(new DeleteCampaignExpectation($campaignExpectation));
    }
}
