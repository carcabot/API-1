<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Entity\CustomerAccount;

class UpdateReferralCode
{
    /**
     * @var CustomerAccount
     */
    private $customer;

    /**
     * @param CustomerAccount $customer
     */
    public function __construct(CustomerAccount $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Gets the user.
     *
     * @return CustomerAccount
     */
    public function getCustomerAccount(): CustomerAccount
    {
        return $this->customer;
    }
}
