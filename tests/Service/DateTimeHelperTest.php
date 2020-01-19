<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\DateTimeHelper;
use PHPUnit\Framework\TestCase;

class DateTimeHelperTest extends TestCase
{
    public function testDateDiff()
    {
        $dateTimeHelper = new DateTimeHelper();

        $yearStart = new \DateTime('2018-11-16 08:19:10');
        $yearEnd = new \DateTime('2019-11-16 08:19:10');

        $monthStart = new \DateTime('2018-11-16 08:19:10');
        $monthEnd = new \DateTime('2018-12-16 08:19:10');

        $dayStart = new \DateTime('2018-11-16 08:19:10');
        $dayEnd = new \DateTime('2018-11-17 08:19:10');

        $hourStart = new \DateTime('2018-11-16 08:19:10');
        $hourEnd = new \DateTime('2018-11-16 09:19:10');

        $minStart = new \DateTime('2018-11-16 08:19:10');
        $minEnd = new \DateTime('2018-11-16 08:20:10');

        $secStart = new \DateTime('2018-11-16 08:19:10');
        $secEnd = new \DateTime('2018-11-16 08:19:11');

        $this->assertEquals($dateTimeHelper->dateDiff('y', $yearStart, $yearEnd), 1);
        $this->assertEquals($dateTimeHelper->dateDiff('m', $monthStart, $monthEnd), 1);
        $this->assertEquals($dateTimeHelper->dateDiff('d', $dayStart, $dayEnd), 1);
        $this->assertEquals($dateTimeHelper->dateDiff('h', $hourStart, $hourEnd), 1);
        $this->assertEquals($dateTimeHelper->dateDiff('i', $minStart, $minEnd), 1);
        $this->assertEquals($dateTimeHelper->dateDiff('s', $secStart, $secEnd), 1);
    }
}
