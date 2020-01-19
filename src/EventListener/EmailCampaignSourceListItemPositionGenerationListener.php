<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\Campaign\ResetEmailCampaignSourceListItemPositions;
use App\Entity\Campaign;
use App\Enum\CampaignStatus;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class EmailCampaignSourceListItemPositionGenerationListener
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

        if (!($controllerResult instanceof Campaign)) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /**
         * @var Campaign
         */
        $campaign = $controllerResult;

        if (CampaignStatus::SCHEDULED !== $campaign->getStatus()->getValue()) {
            return;
        }

        $this->commandBus->handle(new ResetEmailCampaignSourceListItemPositions($campaign));
    }
}
