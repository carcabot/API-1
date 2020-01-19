<?php

declare(strict_types=1);

namespace App\Domain\Command\ApplicationRequest;

use App\Model\ApplicationRequestNumberGenerator;

class UpdateApplicationRequestNumberHandler
{
    /**
     * @var ApplicationRequestNumberGenerator
     */
    private $applicationRequestNumberGenerator;

    /**
     * @param ApplicationRequestNumberGenerator $applicationRequestNumberGenerator
     */
    public function __construct(ApplicationRequestNumberGenerator $applicationRequestNumberGenerator)
    {
        $this->applicationRequestNumberGenerator = $applicationRequestNumberGenerator;
    }

    public function handle(UpdateApplicationRequestNumber $command): void
    {
        $applicationRequest = $command->getApplicationRequest();
        $applicationRequestNumber = $this->applicationRequestNumberGenerator->generate($applicationRequest);

        $applicationRequest->setApplicationRequestNumber($applicationRequestNumber);
    }
}
