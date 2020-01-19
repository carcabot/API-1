<?php

declare(strict_types=1);

namespace App\Domain\Command\ApplicationRequest;

class UpdateApplicationRequestAddonServicesHandler
{
    public function handle(UpdateApplicationRequestAddonServices $command): void
    {
        $applicationRequest = $command->getApplicationRequest();
        $addonServices = [];

        foreach ($applicationRequest->getAddonServices() as $addonService) {
            $parent = $addonService;

            while (null !== $parent->getIsBasedOn()) {
                $parent = $parent->getIsBasedOn();
            }

            if (null !== $parent && false === isset($addonServices[$parent->getId()])) {
                $clone = clone $parent;
                $clone->setIsBasedOn($parent);

                $addonServices[$parent->getId()] = $clone;
            }
        }

        if (\count($addonServices) > 0) {
            $applicationRequest->clearAddonServices();

            foreach ($addonServices as $addonService) {
                $applicationRequest->addAddonService($addonService);
            }
        }
    }
}
