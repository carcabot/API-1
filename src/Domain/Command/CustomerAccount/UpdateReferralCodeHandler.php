<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Model\CustomerAccountReferralCodeGenerator;

class UpdateReferralCodeHandler
{
    /**
     * @var CustomerAccountReferralCodeGenerator
     */
    private $referralCodeGenerator;

    /**
     * @param CustomerAccountReferralCodeGenerator $referralCodeGenerator
     */
    public function __construct(CustomerAccountReferralCodeGenerator $referralCodeGenerator)
    {
        $this->referralCodeGenerator = $referralCodeGenerator;
    }

    public function handle(UpdateReferralCode $command): void
    {
        $customerAccount = $command->getCustomerAccount();
        $referralCode = $this->referralCodeGenerator->generateReferralCode($customerAccount);

        $customerAccount->setReferralCode($referralCode);
    }
}
