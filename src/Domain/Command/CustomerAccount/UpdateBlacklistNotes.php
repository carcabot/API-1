<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Entity\CustomerAccount;
use App\Entity\CustomerBlacklist;

class UpdateBlacklistNotes
{
    /**
     * @var CustomerBlacklist
     */
    private $blacklist;

    /**
     * @var CustomerAccount
     */
    private $customer;

    /**
     * @param CustomerBlacklist $blacklist
     * @param CustomerAccount   $customer
     */
    public function __construct(CustomerBlacklist $blacklist, CustomerAccount $customer)
    {
        $this->blacklist = $blacklist;
        $this->customer = $customer;
    }

    /**
     * Gets the blacklist entry.
     *
     * @return CustomerBlacklist
     */
    public function getBlacklist(): CustomerBlacklist
    {
        return $this->blacklist;
    }

    /**
     * Gets the customer.
     *
     * @return CustomerAccount
     */
    public function getCustomer(): CustomerAccount
    {
        return $this->customer;
    }
}
