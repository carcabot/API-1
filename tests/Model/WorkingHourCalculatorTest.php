<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\OpeningHoursSpecification;
use App\Enum\DayOfWeek;
use App\Model\OpeningHoursSpecificationProcessor;
use App\Model\WorkingHourCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class WorkingHourCalculatorTest extends TestCase
{
    public function testCalculateWorkingHourByInterval()
    {
        $incrementBy = 1;
        $interval = 'day';
        $startDate = new \DateTime('2018-11-15');
        $endDate = new \DateTime('2018-11-16');

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $openingHours = $this->getDummyOpeningHoursSpecification();
        $openingHoursSpecificationProcessor = new OpeningHoursSpecificationProcessor();

        $workingHourCalculator = new WorkingHourCalculator($entityManager, $openingHoursSpecificationProcessor, 'Asia/Singapore');

        $workingDays = $workingHourCalculator->calculateWorkingHourByInterval(clone $startDate, clone $endDate, $openingHours, 0, $incrementBy, $interval, true);
        $workingHours = $workingHourCalculator->calculateWorkingHourByInterval(clone $startDate, clone $endDate, $openingHours, 0, $incrementBy, $interval, false);

        $this->assertEquals($workingDays['existingDate'], new \DateTime('2018-11-16'));
        $this->assertEquals($workingHours['existingDate'], new \DateTime('2018-11-16'));
        $this->assertEquals($workingDays['hours'], 1440);
        $this->assertEquals($workingHours['hours'], 540);
    }

    private function getDummyOpeningHoursSpecification()
    {
        $monday = new OpeningHoursSpecification();
        $monday->setDayOfWeek(new DayOfWeek(DayOfWeek::MON));
        $monday->setOpens(new \DateTime('1:00'));
        $monday->setCloses(new \DateTime('10:00'));

        $tuesday = new OpeningHoursSpecification();
        $tuesday->setDayOfWeek(new DayOfWeek(DayOfWeek::TUE));
        $tuesday->setOpens(new \DateTime('1:00'));
        $tuesday->setCloses(new \DateTime('10:00'));

        $wednesday = new OpeningHoursSpecification();
        $wednesday->setDayOfWeek(new DayOfWeek(DayOfWeek::WED));
        $wednesday->setOpens(new \DateTime('1:00'));
        $wednesday->setCloses(new \DateTime('10:00'));

        $thursday = new OpeningHoursSpecification();
        $thursday->setDayOfWeek(new DayOfWeek(DayOfWeek::THU));
        $thursday->setOpens(new \DateTime('1:00'));
        $thursday->setCloses(new \DateTime('10:00'));

        $friday = new OpeningHoursSpecification();
        $friday->setDayOfWeek(new DayOfWeek(DayOfWeek::FRI));
        $friday->setOpens(new \DateTime('1:00'));
        $friday->setCloses(new \DateTime('10:00'));

        $saturday = new OpeningHoursSpecification();
        $saturday->setDayOfWeek(new DayOfWeek(DayOfWeek::SAT));

        $sunday = new OpeningHoursSpecification();
        $sunday->setDayOfWeek(new DayOfWeek(DayOfWeek::SUN));

        $christmasDay = new OpeningHoursSpecification();
        $christmasDay->setDayOfWeek(new DayOfWeek(DayOfWeek::PUBLIC_HOLIDAY));
        $christmasDay->setValidFrom(new \DateTime('2018-12-25'));
        $christmasDay->setValidThrough(new \DateTime('2018-12-26'));

        return [
            1 => $monday,
            2 => $tuesday,
            3 => $wednesday,
            4 => $thursday,
            5 => $friday,
            6 => $saturday,
            7 => $sunday,
            8 => $christmasDay,
        ];
    }
}
