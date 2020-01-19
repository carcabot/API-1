<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\Campaign;

use App\Domain\Command\Campaign\UpdateCampaignNumber;
use App\Domain\Command\Campaign\UpdateCampaignNumberHandler;
use App\Entity\Campaign;
use App\Model\CampaignNumberGenerator;
use PHPUnit\Framework\TestCase;

class UpdateCampaignNumberTest extends TestCase
{
    public function testUpdateCampaignNumber()
    {
        $length = 8;
        $prefix = 'C-';
        $type = 'campaign';
        $number = 1;

        $campaignPorphecy = $this->prophesize(Campaign::class);
        $campaignPorphecy->setCampaignNumber('C-00000001')->shouldBeCalled();
        $campaign = $campaignPorphecy->reveal();

        $campaignNumberGeneratorProphecy = $this->prophesize(CampaignNumberGenerator::class);
        $campaignNumberGeneratorProphecy->generate($campaign)->willReturn(\sprintf('%s%s', $prefix, \str_pad((string) $number, $length, '0', STR_PAD_LEFT)));
        $campaignNumberGenerator = $campaignNumberGeneratorProphecy->reveal();

        $updateCampaignNumberHandler = new UpdateCampaignNumberHandler($campaignNumberGenerator);
        $updateCampaignNumberHandler->handle(new UpdateCampaignNumber($campaign));
    }
}
