<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\CustomerAccount;

use App\Entity\CustomerAccount;

/**
 * Builds the contact update data for web service consumption.
 */
class BuildContactUpdateData
{
    /**
     * @var CustomerAccount
     */
    private $customerAccount;

    /**
     * @var string|null
     */
    private $previousCustomerName;

    /**
     * @param CustomerAccount $customerAccount
     * @param string|null     $previousCustomerName
     */
    public function __construct(CustomerAccount $customerAccount, ?string $previousCustomerName)
    {
        $this->customerAccount = $customerAccount;
        $this->previousCustomerName = $previousCustomerName;
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
     * Gets the previous name for customer.
     *
     * @return string|null
     */
    public function getPreviousCustomerName(): ?string
    {
        return $this->previousCustomerName;
    }
}
