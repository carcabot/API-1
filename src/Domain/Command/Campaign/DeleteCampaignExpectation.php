<?php

declare(strict_types=1);

namespace App\Domain\Command\Campaign;

use App\Entity\CampaignExpectation;

class DeleteCampaignExpectation
{
    /**
     * @var CampaignExpectation
     */
    private $campaignExpectation;

    /**
     * @param CampaignExpectation $campaignExpectation
     */
    public function __construct(CampaignExpectation $campaignExpectation)
    {
        $this->campaignExpectation = $campaignExpectation;
    }

    /**
     * @return CampaignExpectation
     */
    public function getCampaignExpectation(): CampaignExpectation
    {
        return $this->campaignExpectation;
    }
}
