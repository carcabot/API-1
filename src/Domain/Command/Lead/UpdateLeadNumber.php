<?php

declare(strict_types=1);

namespace App\Domain\Command\Lead;

use App\Entity\Lead;

/**
 * Updates lead number.
 */
class UpdateLeadNumber
{
    /**
     * @var Lead
     */
    private $lead;

    /**
     * @param Lead $lead
     */
    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * Gets the lead.
     *
     * @return Lead
     */
    public function getLead(): Lead
    {
        return $this->lead;
    }
}
