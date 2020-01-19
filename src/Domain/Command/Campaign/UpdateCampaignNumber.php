<?php

declare(strict_types=1);

namespace App\Domain\Command\Campaign;

use App\Entity\Campaign;

/**
 * Updates campaign number.
 */
class UpdateCampaignNumber
{
    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @param Campaign $campaign
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Gets the campaign.
     *
     * @return Campaign
     */
    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }
}
