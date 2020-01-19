<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

class UpdateBlacklistStatusHandler
{
    public function handle(UpdateBlacklistStatus $command): bool
    {
        $blacklist = $command->getBlacklist();
        $customer = $command->getCustomer();

        if (null === $customer->getDateBlacklisted() && 'ADD' === $blacklist->getAction()) {
            $customer->setDateBlacklisted(new \DateTime());
            $blacklist->setCustomer($customer);

            return true;
        } elseif (null !== $customer->getDateBlacklisted() && 'REMOVE' === $blacklist->getAction()) {
            $customer->setDateBlacklisted(null);
            $blacklist->setCustomer($customer);

            return true;
        }

        return false;
    }
}
