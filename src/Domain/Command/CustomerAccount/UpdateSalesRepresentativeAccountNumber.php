<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Entity\CustomerAccount;

/**
 * Updates sales representative account number.
 */
class UpdateSalesRepresentativeAccountNumber
{
    /**
     * @var CustomerAccount
     */
    private $employer;

    /**
     * @var CustomerAccount
     */
    private $salesRepresentative;

    /**
     * @param CustomerAccount $employer
     * @param CustomerAccount $salesRepresentative
     */
    public function __construct(CustomerAccount $employer, CustomerAccount $salesRepresentative)
    {
        $this->employer = $employer;
        $this->salesRepresentative = $salesRepresentative;
    }

    /**
     * Gets the employer.
     *
     * @return CustomerAccount
     */
    public function getEmployer(): CustomerAccount
    {
        return $this->employer;
    }

    /**
     * Gets the salesRepresentative.
     *
     * @return CustomerAccount
     */
    public function getSalesRepresentative(): CustomerAccount
    {
        return $this->salesRepresentative;
    }
}
