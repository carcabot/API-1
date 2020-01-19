<?php

declare(strict_types=1);

namespace App\Domain\Command\Partner;

class GetNextPayoutDateHandler
{
    public function handle(GetNextPayoutDate $command): ?\DateTime
    {
        $partner = $command->getPartner();
        $previousEndDate = $command->getPreviousEndDate();
        $appTimezone = $command->getAppTimezone();
        $nextPayoutDate = null;

        if (null !== $partner->getPayoutCycle()->getValue() && null !== $partner->getPayoutCycle()->getUnitCode()) {
            $cycleValue = $partner->getPayoutCycle()->getValue();

            switch ($partner->getPayoutCycle()->getUnitCode()) {
                case 'ANN':
                    $timeUnit = 'year';
                    break;
                case 'DAY':
                    if ('1' === $cycleValue) {
                        $cycleValue = '0';
                    }
                    $timeUnit = 'day';
                    break;
                case 'MON':
                    $timeUnit = 'month';
                    break;
                case 'WEE':
                    $timeUnit = 'week';
                    break;
                default:
                    return $nextPayoutDate;
            }

            $nextPayoutDate = new \DateTime($previousEndDate->format('Y-m-d'));
            $nextPayoutDate->setTimezone($appTimezone)->modify('+1 day')->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));
            $nextPayoutDate->modify('+'.$cycleValue.' '.$timeUnit);
        }

        return $nextPayoutDate;
    }
}
