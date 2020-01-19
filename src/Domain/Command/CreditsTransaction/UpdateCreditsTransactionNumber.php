<?php

declare(strict_types=1);

namespace App\Domain\Command\CreditsTransaction;

use App\Entity\CreditsTransaction;

/**
 * Update credits transaction number.
 */
class UpdateCreditsTransactionNumber
{
    /**
     * @var CreditsTransaction
     */
    private $creditsTransaction;

    /**
     * UpdateCreditsTransactionNumber constructor.
     *
     * @param CreditsTransaction $creditsTransaction
     */
    public function __construct(CreditsTransaction $creditsTransaction)
    {
        $this->creditsTransaction = $creditsTransaction;
    }

    /**
     * @return CreditsTransaction
     */
    public function getCreditsTransaction(): CreditsTransaction
    {
        return $this->creditsTransaction;
    }
}
