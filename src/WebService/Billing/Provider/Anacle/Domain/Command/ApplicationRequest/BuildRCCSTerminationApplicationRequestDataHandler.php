<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\ApplicationRequest;

class BuildRCCSTerminationApplicationRequestDataHandler
{
    public function handle(BuildRCCSTerminationApplicationRequestData $command): array
    {
        $applicationRequest = $command->getApplicationRequest();

        $contractNumber = null;
        $terminationDate = null;

        if (null !== $applicationRequest->getContract()) {
            $contractNumber = $applicationRequest->getContract()->getContractNumber();
        }

        if (null !== $applicationRequest->getTerminationDate()) {
            $terminationDate = $applicationRequest->getTerminationDate();
            $terminationDate->setTimezone(new \DateTimeZone('Asia/Singapore'));
            $terminationDate = $terminationDate->format('Y-m-d\TH:i:s');
        }
        // @todo fill the array
        $rccsTerminationData = [
            'CRMRCCSTerminationRequestNumber' => $applicationRequest->getApplicationRequestNumber(),
            'FRCContractNumber' => $contractNumber,
            'TerminationDate' => $terminationDate,
        ];

        return $rccsTerminationData;
    }
}
