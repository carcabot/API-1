<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest;

use App\Entity\ApplicationRequest;

class BuildRCCSTerminationApplicationRequestData
{
    /**
     * @var ApplicationRequest
     */
    private $applicationRequest;

    /**
     * @param ApplicationRequest $applicationRequest
     */
    public function __construct(ApplicationRequest $applicationRequest)
    {
        $this->applicationRequest = $applicationRequest;
    }

    /**
     * Gets the applicationRequest.
     *
     * @return ApplicationRequest
     */
    public function getApplicationRequest(): ApplicationRequest
    {
        return $this->applicationRequest;
    }
}
