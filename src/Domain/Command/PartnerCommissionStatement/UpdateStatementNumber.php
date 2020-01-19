<?php

declare(strict_types=1);

namespace App\Domain\Command\PartnerCommissionStatement;

use App\Entity\PartnerCommissionStatement;

/**
 * Updates partner commission statement number.
 */
class UpdateStatementNumber
{
    /**
     * @var PartnerCommissionStatement
     */
    private $partnerCommissionStatement;

    /**
     * @param PartnerCommissionStatement $partnerCommissionStatement
     */
    public function __construct(PartnerCommissionStatement $partnerCommissionStatement)
    {
        $this->partnerCommissionStatement = $partnerCommissionStatement;
    }

    /**
     * Gets the partnerCommissionStatement.
     *
     * @return PartnerCommissionStatement
     */
    public function getPartnerCommissionStatement(): PartnerCommissionStatement
    {
        return $this->partnerCommissionStatement;
    }
}
