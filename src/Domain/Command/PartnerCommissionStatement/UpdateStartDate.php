<?php

declare(strict_types=1);

namespace App\Domain\Command\PartnerCommissionStatement;

use App\Entity\PartnerCommissionStatement;

/**
 * Updates startDate.
 */
class UpdateStartDate
{
    /**
     * @var PartnerCommissionStatement
     */
    private $commissionStatement;

    /**
     * @var \DateTime
     */
    private $previousEndDate;

    /**
     * @var \DateTimeZone
     */
    private $appTimezone;

    /**
     * @param PartnerCommissionStatement $commissionStatement
     * @param \DateTime                  $previousEndDate
     * @param \DateTimeZone              $appTimezone
     */
    public function __construct(PartnerCommissionStatement $commissionStatement, \DateTime $previousEndDate, \DateTimeZone $appTimezone)
    {
        $this->commissionStatement = $commissionStatement;
        $this->previousEndDate = $previousEndDate;
        $this->appTimezone = $appTimezone;
    }

    /**
     * Gets commissionStatement.
     *
     * @return PartnerCommissionStatement
     */
    public function getCommissionStatement(): PartnerCommissionStatement
    {
        return $this->commissionStatement;
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
