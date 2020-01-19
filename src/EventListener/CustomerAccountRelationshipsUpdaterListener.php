<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\CustomerAccount\UpdateCategories;
use App\Domain\Command\CustomerAccount\UpdateRelationships;
use App\Entity\CustomerAccountRelationship;
use App\Enum\CustomerRelationshipType;
use App\Model\CustomerAccountPortalEnableUpdater;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class CustomerAccountRelationshipsUpdaterListener
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var CustomerAccountPortalEnableUpdater
     */
    private $customerAccountPortalEnableUpdater;

    /**
     * @param CommandBus                         $commandBus
     * @param CustomerAccountPortalEnableUpdater $customerAccountPortalEnableUpdater
     */
    public function __construct(CommandBus $commandBus, CustomerAccountPortalEnableUpdater $customerAccountPortalEnableUpdater)
    {
        $this->commandBus = $commandBus;
        $this->customerAccountPortalEnableUpdater = $customerAccountPortalEnableUpdater;
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof CustomerAccountRelationship)) {
            return;
        }

        /** @var CustomerAccountRelationship $relationship */
        $relationship = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        $this->commandBus->handle(new UpdateRelationships($relationship));
        $this->commandBus->handle(new UpdateCategories($relationship));

        if (CustomerRelationshipType::CONTACT_PERSON === $relationship->getType()->getValue()) {
            $date = new \DateTime();
            $date->sub(new \DateInterval('P90D'));
            $this->customerAccountPortalEnableUpdater->updateCustomerPortalEnabled($relationship->getFrom(), $date);
        }
    }
}
