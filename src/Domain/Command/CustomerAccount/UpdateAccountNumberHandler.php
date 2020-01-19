<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Model\CustomerAccountNumberGenerator;

class UpdateAccountNumberHandler
{
    /**
     * @var CustomerAccountNumberGenerator
     */
    private $customerAccountNumberGenerator;

    /**
     * @param CustomerAccountNumberGenerator $customerAccountNumberGenerator
     */
    public function __construct(CustomerAccountNumberGenerator $customerAccountNumberGenerator)
    {
        $this->customerAccountNumberGenerator = $customerAccountNumberGenerator;
    }

    public function handle(UpdateAccountNumber $command): void
    {
        $customerAccount = $command->getCustomerAccount();
        $customerAccountNumber = $this->customerAccountNumberGenerator->generate($customerAccount);

        $customerAccount->setAccountNumber($customerAccountNumber);
    }
}
