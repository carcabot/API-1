<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\PartnerCommissionStatement;

use App\Domain\Command\PartnerCommissionStatement\UpdateEndDate;
use App\Domain\Command\PartnerCommissionStatement\UpdateEndDateHandler;
use App\Entity\PartnerCommissionStatement;
use PHPUnit\Framework\TestCase;

class UpdateEndDateHandlerTest extends TestCase
{
    public function testUpdateEndDate()
    {
        $timezone = new \DateTimeZone('Asia/Singapore');
        $jobTime = new \DateTime('2018-06-15');
        $jobTime->setTimezone($timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));

        $supposedEndDate = new \DateTime('2018-06-14');
        $supposedEndDate->setTimezone($timezone)->setTime(23, 59, 59)->setTimezone(new \DateTimeZone('UTC'));

        $partnerCommissionStatementProphecy = $this->prophesize(PartnerCommissionStatement::class);
        $partnerCommissionStatementProphecy->setEndDate($supposedEndDate)->shouldBeCalled();
        $partnerCommissionStatement = $partnerCommissionStatementProphecy->reveal();

        $updateEndDateHandler = new UpdateEndDateHandler();
        $updateEndDateHandler->handle(new UpdateEndDate($partnerCommissionStatement, $jobTime, $timezone));
    }
}
