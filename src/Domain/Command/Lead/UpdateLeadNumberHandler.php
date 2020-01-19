<?php

declare(strict_types=1);

namespace App\Domain\Command\Lead;

use App\Model\LeadNumberGenerator;

class UpdateLeadNumberHandler
{
    /**
     * @var LeadNumberGenerator
     */
    private $leadNumberGenerator;

    /**
     * @param LeadNumberGenerator $leadNumberGenerator
     */
    public function __construct(LeadNumberGenerator $leadNumberGenerator)
    {
        $this->leadNumberGenerator = $leadNumberGenerator;
    }

    public function handle(UpdateLeadNumber $command): void
    {
        $lead = $command->getLead();
        $leadNumber = $this->leadNumberGenerator->generate($lead);

        $lead->setLeadNumber($leadNumber);
    }
}
