<?php

declare(strict_types=1);

namespace App\Domain\Command\Campaign;

use App\Model\CampaignNumberGenerator;

class UpdateCampaignNumberHandler
{
    /**
     * @var CampaignNumberGenerator
     */
    private $campaignNumberGenerator;

    /**
     * @param CampaignNumberGenerator $campaignNumberGenerator
     */
    public function __construct(CampaignNumberGenerator $campaignNumberGenerator)
    {
        $this->campaignNumberGenerator = $campaignNumberGenerator;
    }

    public function handle(UpdateCampaignNumber $command): void
    {
        $campaign = $command->getCampaign();
        $campaignNumber = $this->campaignNumberGenerator->generate($campaign);

        $campaign->setCampaignNumber($campaignNumber);
    }
}
