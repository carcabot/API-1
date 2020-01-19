<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Enworkz\Domain\Command\CustomerAccount;

use App\Entity\CustomerAccount;

class BuildContactUpdateData
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
