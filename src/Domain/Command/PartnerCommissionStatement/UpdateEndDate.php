<?php

declare(strict_types=1);

namespace App\Domain\Command\PartnerCommissionStatement;

use App\Entity\PartnerCommissionStatement;

/**
 * Updates endDate.
 */
class UpdateEndDate
{
    /**
     * @var PartnerCommissionStatement
     */
    private $commissionStatement;

    /**
     * @var \DateTime
     */
    private $jobTime;

    /**
     * @var \DateTimeZone
     */
    private $appTimezone;

    /**
     * @param PartnerCommissionStatement $commissionStatement
     * @param \DateTime                  $jobTime
     * @param \DateTimeZone              $appTimezone
     */
    public function __construct(PartnerCommissionStatement $commissionStatement, \DateTime $jobTime, \DateTimeZone $appTimezone)
    {
        $this->commissionStatement = $commissionStatement;
        $this->jobTime = $jobTime;
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
     * Gets the jobTime.
     *
     * @return \DateTime
     */
    public function getJobTime(): \DateTime
    {
        return $this->jobTime;
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
