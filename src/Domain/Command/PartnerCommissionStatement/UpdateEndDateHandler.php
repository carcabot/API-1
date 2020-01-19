<?php

declare(strict_types=1);

namespace App\Domain\Command\PartnerCommissionStatement;

class UpdateEndDateHandler
{
    public function handle(UpdateEndDate $command): void
    {
        $commissionStatement = $command->getCommissionStatement();
        $jobTime = $command->getJobTime();
        $appTimezone = $command->getAppTimezone();

        $endDate = clone $jobTime;
        $endDate->setTimezone($appTimezone)->modify('-1 day')->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $commissionStatement->setEndDate($endDate);
    }
}
