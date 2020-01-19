<?php

declare(strict_types=1);

namespace App\Domain\Command\ServiceLevelAgreementAction;

use App\Model\ServiceLevelAgreementActionUpdater;

class UpdateServiceLevelAgreementActionHandler
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

    public function handle(UpdateServiceLevelAgreementAction $command): void
    {
        $ticket = $command->getTicket();
        $initialStatus = $command->getInitialStatus();

        $this->serviceLevelAgreementActionUpdater->update($ticket, $initialStatus);
    }
}
