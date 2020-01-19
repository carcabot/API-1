<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Entity\CustomerAccount;
use App\Entity\UpdateCreditsAction;

/**
 * Updates money credits actions.
 */
class UpdateMoneyCreditsActions
{
    /**
     * @var CustomerAccount
     */
    private $customerAccount;

    /**
     * @var UpdateCreditsAction
     */
    private $creditsAction;

    /**
     * @param CustomerAccount     $customerAccount
     * @param UpdateCreditsAction $creditsAction
     */
    public function __construct(CustomerAccount $customerAccount, UpdateCreditsAction $creditsAction)
    {
        $this->customerAccount = $customerAccount;
        $this->creditsAction = $creditsAction;
    }

    /**
     * Gets the customerAccount.
     *
     * @return CustomerAccount
     */
    public function getCustomerAccount(): CustomerAccount
    {
        return $this->customerAccount;
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
