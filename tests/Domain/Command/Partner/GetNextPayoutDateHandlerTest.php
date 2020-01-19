<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\Partner;

use App\Domain\Command\Partner\GetNextPayoutDate;
use App\Domain\Command\Partner\GetNextPayoutDateHandler;
use App\Entity\Partner;
use App\Entity\QuantitativeValue;
use PHPUnit\Framework\TestCase;

class GetNextPayoutDateHandlerTest extends TestCase
{
    public function testPayoutCycleOneDay()
    {
        $timezone = new \DateTimeZone('Asia/Singapore');
        $previousEndDate = new \DateTime('2018-06-14');
        $previousEndDate->setTimezone($timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $supposedJobScheduleDate = new \DateTime('2018-06-15');
        $supposedJobScheduleDate->setTimezone($timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));

        $partnerProphecy = $this->prophesize(Partner::class);
        $partnerProphecy->getPayoutCycle()->willReturn(new QuantitativeValue('1', null, null, 'DAY'));
        $partner = $partnerProphecy->reveal();

        $getNextPayoutDateHandler = new GetNextPayoutDateHandler();
        $jobScheduleTime = $getNextPayoutDateHandler->handle(new GetNextPayoutDate($partner, $previousEndDate, $timezone));

        $this->assertEquals($supposedJobScheduleDate, $jobScheduleTime);
    }

    public function testPayoutCycleNotOneDay()
    {
        $timezone = new \DateTimeZone('Asia/Singapore');
        $previousEndDate = new \DateTime('2018-06-14');
        $previousEndDate->setTimezone($timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $supposedJobScheduleDate = new \DateTime('2018-06-17');
        $supposedJobScheduleDate->setTimezone($timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));

        $partnerProphecy = $this->prophesize(Partner::class);
        $partnerProphecy->getPayoutCycle()->willReturn(new QuantitativeValue('2', null, null, 'DAY'));
        $partner = $partnerProphecy->reveal();

        $getNextPayoutDateHandler = new GetNextPayoutDateHandler();
        $jobScheduleTime = $getNextPayoutDateHandler->handle(new GetNextPayoutDate($partner, $previousEndDate, $timezone));

        $this->assertEquals($supposedJobScheduleDate, $jobScheduleTime);
    }

    public function testPayoutCycleByWeek()
    {
        $timezone = new \DateTimeZone('Asia/Singapore');
        $previousEndDate = new \DateTime('2018-06-14');
        $previousEndDate->setTimezone($timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $supposedJobScheduleDate = new \DateTime('2018-07-06');
        $supposedJobScheduleDate->setTimezone($timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));

        $partnerProphecy = $this->prophesize(Partner::class);
        $partnerProphecy->getPayoutCycle()->willReturn(new QuantitativeValue('3', null, null, 'WEE'));
        $partner = $partnerProphecy->reveal();

        $getNextPayoutDateHandler = new GetNextPayoutDateHandler();
        $jobScheduleTime = $getNextPayoutDateHandler->handle(new GetNextPayoutDate($partner, $previousEndDate, $timezone));

        $this->assertEquals($supposedJobScheduleDate, $jobScheduleTime);
    }

    public function testPayoutCycleByMonth()
    {
        $timezone = new \DateTimeZone('Asia/Singapore');
        $previousEndDate = new \DateTime('2018-06-14');
        $previousEndDate->setTimezone($timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $supposedJobScheduleDate = new \DateTime('2018-08-15');
        $supposedJobScheduleDate->setTimezone($timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));

        $partnerProphecy = $this->prophesize(Partner::class);
        $partnerProphecy->getPayoutCycle()->willReturn(new QuantitativeValue('2', null, null, 'MON'));
        $partner = $partnerProphecy->reveal();

        $getNextPayoutDateHandler = new GetNextPayoutDateHandler();
        $jobScheduleTime = $getNextPayoutDateHandler->handle(new GetNextPayoutDate($partner, $previousEndDate, $timezone));

        $this->assertEquals($supposedJobScheduleDate, $jobScheduleTime);
    }

    public function testPayoutCycleByYear()
    {
        $timezone = new \DateTimeZone('Asia/Singapore');
        $previousEndDate = new \DateTime('2018-06-14');
        $previousEndDate->setTimezone($timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $supposedJobScheduleDate = new \DateTime('2019-06-15');
        $supposedJobScheduleDate->setTimezone($timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));

        $partnerProphecy = $this->prophesize(Partner::class);
        $partnerProphecy->getPayoutCycle()->willReturn(new QuantitativeValue('1', null, null, 'ANN'));
        $partner = $partnerProphecy->reveal();

        $getNextPayoutDateHandler = new GetNextPayoutDateHandler();
        $jobScheduleTime = $getNextPayoutDateHandler->handle(new GetNextPayoutDate($partner, $previousEndDate, $timezone));

        $this->assertEquals($supposedJobScheduleDate, $jobScheduleTime);
    }

    public function testPayoutCycleIsNull()
    {
        $timezone = new \DateTimeZone('Asia/Singapore');
        $previousEndDate = new \DateTime('2018-06-14');
        $previousEndDate->setTimezone($timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $partnerProphecy = $this->prophesize(Partner::class);
        $partnerProphecy->getPayoutCycle()->willReturn(new QuantitativeValue(null, null, null, null));
        $partner = $partnerProphecy->reveal();

        $getNextPayoutDateHandler = new GetNextPayoutDateHandler();
        $jobScheduleTime = $getNextPayoutDateHandler->handle(new GetNextPayoutDate($partner, $previousEndDate, $timezone));

        $this->assertNull($jobScheduleTime);
    }

    public function testPayoutCycleIsInvalid()
    {
        $timezone = new \DateTimeZone('Asia/Singapore');
        $previousEndDate = new \DateTime('2018-06-14');
        $previousEndDate->setTimezone($timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $partnerProphecy = $this->prophesize(Partner::class);
        $partnerProphecy->getPayoutCycle()->willReturn(new QuantitativeValue('1', null, null, 'HUR'));
        $partner = $partnerProphecy->reveal();

        $getNextPayoutDateHandler = new GetNextPayoutDateHandler();
        $jobScheduleTime = $getNextPayoutDateHandler->handle(new GetNextPayoutDate($partner, $previousEndDate, $timezone));

        $this->assertNull($jobScheduleTime);
    }
}
