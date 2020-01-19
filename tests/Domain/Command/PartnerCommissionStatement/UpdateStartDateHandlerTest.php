<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\PartnerCommissionStatement;

use App\Domain\Command\PartnerCommissionStatement\UpdateStartDate;
use App\Domain\Command\PartnerCommissionStatement\UpdateStartDateHandler;
use App\Entity\PartnerCommissionStatement;
use PHPUnit\Framework\TestCase;

class UpdateStartDateHandlerTest extends TestCase
{
    public function testUpdateStartDate()
    {
        $timezone = new \DateTimeZone('Asia/Singapore');
        $previousEndDate = new \DateTime('2018-06-14');
        $previousEndDate->setTimezone($timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $supposedStartDate = new \DateTime('2018-06-15');
        $supposedStartDate->setTimezone($timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));

        $partnerCommissionStatementProphecy = $this->prophesize(PartnerCommissionStatement::class);
        $partnerCommissionStatementProphecy->setStartDate($supposedStartDate)->shouldBeCalled();
        $partnerCommissionStatement = $partnerCommissionStatementProphecy->reveal();

        $updateStartDateHandler = new UpdateStartDateHandler();
        $updateStartDateHandler->handle(new UpdateStartDate($partnerCommissionStatement, $previousEndDate, $timezone));
    }
}
