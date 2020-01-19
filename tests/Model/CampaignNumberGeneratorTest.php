<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Campaign;
use App\Enum\CampaignStatus;
use App\Model\CampaignNumberGenerator;
use App\Model\RunningNumberGenerator;
use PHPUnit\Framework\TestCase;

class CampaignNumberGeneratorTest extends TestCase
{
    public function testGenerateCampaignNumber()
    {
        $length = 9;
        $prefix = 'C';
        $type = 'campaign';

        $campaignProphecy = $this->prophesize(Campaign::class);
        $campaignProphecy->getStatus()->willReturn(new CampaignStatus(CampaignStatus::NEW));
        $campaign = $campaignProphecy->reveal();

        $runningNumberGeneratorProphecy = $this->prophesize(RunningNumberGenerator::class);
        $runningNumberGeneratorProphecy->getNextNumber($type, (string) $length)->shouldBeCalled()->willReturn(1);
        $runningNumberGenerator = $runningNumberGeneratorProphecy->reveal();

        $campaignNumberGenerator = new CampaignNumberGenerator($runningNumberGenerator);
        $campaignNumber = $campaignNumberGenerator->generate($campaign);

        $this->assertEquals($campaignNumber, 'C000000001');
    }
}
