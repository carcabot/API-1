<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 25/4/19
 * Time: 1:00 PM.
 */

namespace App\Tests\Model;

use App\Entity\OpeningHoursSpecification;
use App\Enum\DayOfWeek;
use App\Model\OpeningHoursSpecificationProcessor;
use PHPUnit\Framework\TestCase;

class OpeningHoursSpecificationProcessorTest extends TestCase
{
    public function testProcessOpeningHoursWithDayOfWeekAsMON()
    {
        $currentDate = new \DateTime('2019-05-06');

        $openingHoursSpecificationProphecy = $this->prophesize(OpeningHoursSpecification::class);
        $openingHoursSpecificationProphecy->getDayOfWeek()->willReturn(new DayOfWeek(DayOfWeek::MON));
        $openingHoursSpecificationProphecy->getValidFrom()->willReturn(new \DateTime('2019-02-06'));
        $openingHoursSpecificationProphecy->getValidThrough()->willReturn(new \DateTime('2019-07-06'));
        $openingHoursSpecificationProphecy->getOpens()->willReturn(new \DateTime('2019-04-06'));
        $openingHoursSpecificationProphecy->getCloses()->willReturn(new \DateTime('2019-08-06'));
        $openingHoursSpecification = $openingHoursSpecificationProphecy->reveal();

        $openHours = [$openingHoursSpecification];
        $expectedResult = ['opens' => new \DateTime('2019-04-06'), 'close' => new \DateTime('2019-08-06'), 'paused' => true];

        $openingHoursSpecificationProcessor = new OpeningHoursSpecificationProcessor();
        $actualResult = $openingHoursSpecificationProcessor->processOpeningHour($currentDate, $openHours, true);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testProcessOpeningHoursWithDayOfWeekAsPublicHoliday()
    {
        $currentDate = new \DateTime('2019-05-06');

        $openingHoursSpecificationProphecy = $this->prophesize(OpeningHoursSpecification::class);
        $openingHoursSpecificationProphecy->getDayOfWeek()->willReturn(new DayOfWeek(DayOfWeek::PUBLIC_HOLIDAY));
        $openingHoursSpecificationProphecy->getValidFrom()->willReturn(new \DateTime('2019-02-06'));
        $openingHoursSpecificationProphecy->getValidThrough()->willReturn(new \DateTime('2019-07-06'));
        $openingHoursSpecificationProphecy->getOpens()->willReturn(new \DateTime('2019-04-06'));
        $openingHoursSpecificationProphecy->getCloses()->willReturn(new \DateTime('2019-08-06'));
        $openingHoursSpecification = $openingHoursSpecificationProphecy->reveal();

        $openHours = [$openingHoursSpecification];
        $expectedResult = ['opens' => null, 'close' => null, 'paused' => false];

        $openingHoursSpecificationProcessor = new OpeningHoursSpecificationProcessor();
        $actualResult = $openingHoursSpecificationProcessor->processOpeningHour($currentDate, $openHours, false);

        $this->assertEquals($expectedResult, $actualResult);
    }
}
