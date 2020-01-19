<?php

declare(strict_types=1);

namespace App\Domain\Command\PartnerCommissionStatement;

class UpdateStartDateHandler
{
    public function handle(UpdateStartDate $command): void
    {
        $commissionStatement = $command->getCommissionStatement();
        $previousEndDate = $command->getPreviousEndDate();
        $appTimezone = $command->getAppTimezone();

        $startDate = clone $previousEndDate;
        $startDate->setTimezone($appTimezone)->modify('+1 day')->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));

        $commissionStatement->setStartDate($startDate);
    }
}
