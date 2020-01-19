<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\WithdrawCreditsAction;

use App\Entity\WithdrawCreditsAction;

/**
 * Build withdraw credits action for webservice consumption.
 */
class BuildWithdrawCreditsTransactionData
{
    /**
     * @var WithdrawCreditsAction
     */
    private $withdrawCreditsAction;

    /**
     * @param WithdrawCreditsAction $withdrawCreditsAction
     */
    public function __construct(WithdrawCreditsAction $withdrawCreditsAction)
    {
        $this->withdrawCreditsAction = $withdrawCreditsAction;
    }

    /**
     * @return WithdrawCreditsAction
     */
    public function getWithdrawCreditsAction(): WithdrawCreditsAction
    {
        return $this->withdrawCreditsAction;
    }
}
