<?php

declare(strict_types=1);

namespace App\Domain\Command\ApplicationRequest;

use App\Enum\ApplicationRequestType;

class UpdateCustomizedHandler
{
    public function handle(UpdateCustomized $command): void
    {
        $applicationRequest = $command->getApplicationRequest();
        $tariffRate = $applicationRequest->getTariffRate();

        if (null !== $tariffRate && ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
            $applicationRequest->setCustomized($tariffRate->isCustomizable());
        }
    }
}
