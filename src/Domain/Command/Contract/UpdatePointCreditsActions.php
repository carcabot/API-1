<?php

declare(strict_types=1);

namespace App\Domain\Command\Contract;

use App\Entity\Contract;
use App\Entity\UpdateCreditsAction;

/**
 * Updates point credits actions.
 */
class UpdatePointCreditsActions
{
    /**
     * @var Contract
     */
    private $contract;

    /**
     * @var UpdateCreditsAction
     */
    private $creditsAction;

    /**
     * @param Contract            $contract
     * @param UpdateCreditsAction $creditsAction
     */
    public function __construct(Contract $contract, UpdateCreditsAction $creditsAction)
    {
        $this->contract = $contract;
        $this->creditsAction = $creditsAction;
    }

    /**
     * Gets the contract.
     *
     * @return Contract
     */
    public function getContract(): Contract
    {
        return $this->contract;
    }

    /**
     * Gets the creditsAction.
     *
     * @return UpdateCreditsAction
     */
    public function getCreditsAction(): UpdateCreditsAction
    {
        return $this->creditsAction;
    }
}
