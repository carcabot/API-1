<?php

declare(strict_types=1);

namespace App\Domain\Command\Campaign;

use App\Entity\CampaignObjective;

class DeleteCampaignObjective
{
    /**
     * @var CampaignObjective
     */
    private $campaignObjective;

    /**
     * @param CampaignObjective $campaignObjective
     */
    public function __construct(CampaignObjective $campaignObjective)
    {
        $this->campaignObjective = $campaignObjective;
    }

    /**
     * @return CampaignObjective
     */
    public function getCampaignObjective(): CampaignObjective
    {
        return $this->campaignObjective;
    }
}
