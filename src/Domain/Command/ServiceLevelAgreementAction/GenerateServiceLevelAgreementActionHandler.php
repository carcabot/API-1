<?php

declare(strict_types=1);

namespace App\Domain\Command\ServiceLevelAgreementAction;

use App\Model\ServiceLevelAgreementActionUpdater;

class GenerateServiceLevelAgreementActionHandler
{
    /**
     * @var ServiceLevelAgreementActionUpdater
     */
    private $serviceLevelAgreementActionUpdater;

    /**
     * @param ServiceLevelAgreementActionUpdater $serviceLevelAgreementActionUpdater
     */
    public function __construct(ServiceLevelAgreementActionUpdater $serviceLevelAgreementActionUpdater)
    {
        $this->serviceLevelAgreementActionUpdater = $serviceLevelAgreementActionUpdater;
    }

    public function handle(GenerateServiceLevelAgreementAction $command): void
    {
        $ticket = $command->getTicket();
        $initialStatus = $command->getInitialStatus();

        $this->serviceLevelAgreementActionUpdater->generate($ticket, $initialStatus);
    }
}
