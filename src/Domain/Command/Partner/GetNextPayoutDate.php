<?php

declare(strict_types=1);

namespace App\Domain\Command\Partner;

use App\Entity\Partner;

/**
 * Gets the next payout date.
 */
class GetNextPayoutDate
{
    /**
     * @var Partner
     */
    private $partner;

    /**
     * @var \DateTime
     */
    private $previousEndDate;

    /**
     * @var \DateTimeZone
     */
    private $appTimezone;

    /**
     * @param Partner       $partner
     * @param \DateTime     $previousEndDate
     * @param \DateTimeZone $appTimezone
     */
    public function __construct(Partner $partner, \DateTime $previousEndDate, \DateTimeZone $appTimezone)
    {
        $this->partner = $partner;
        $this->previousEndDate = $previousEndDate;
        $this->appTimezone = $appTimezone;
    }

    /**
     * Gets partner.
     *
     * @return Partner
     */
    public function getPartner(): Partner
    {
        return $this->partner;
    }

    /**
     * Gets the previousEndDate.
     *
     * @return \DateTime
     */
    public function getPreviousEndDate(): \DateTime
    {
        return $this->previousEndDate;
    }

    /**
     * Gets the appTimezone.
     *
     * @return \DateTimeZone
     */
    public function getAppTimezone(): \DateTimeZone
    {
        return $this->appTimezone;
    }
}
