<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\CustomerAccount;
use App\Enum\AccountType;

class CustomerAccountReferralCodeGenerator
{
    public function generateReferralCode(CustomerAccount $customer)
    {
        $customerType = $customer->getType()->getValue();
        $customerDetails = AccountType::INDIVIDUAL === $customerType ? $customer->getPersonDetails() : $customer->getCorporationDetails();
        $customerName = null === $customerDetails ? '' : $customerDetails->getName();
        if (null !== $customerName) {
            $customerName = \preg_replace('/[^a-zA-Z0-9]/', '', $customerName);
            $customerName = \substr($customerName, 0, 3);
            $customerName = \trim($customerName);
            $referralCode = \md5(\uniqid($customerName));
            $referralCode = \strtoupper($customerName.\substr($referralCode, 0, (8 - \strlen($customerName))));

            return $referralCode;
        }

        return null;
    }
}
