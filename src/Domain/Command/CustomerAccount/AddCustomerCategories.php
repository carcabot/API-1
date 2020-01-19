<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Entity\CustomerAccount;

/**
 * Updates account number.
 */
class AddCustomerCategories
{
    /**
     * @var CustomerAccount
     */
    private $customerAccount;

    /**
     * @param CustomerAccount $customerAccount
     */
    public function __construct(CustomerAccount $customerAccount)
    {
        $this->customerAccount = $customerAccount;
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
}
