<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Enum\AccountCategory;
use App\Enum\CustomerRelationshipType;

class UpdateCategoriesHandler
{
    public function handle(UpdateCategories $command): void
    {
        $relationship = $command->getRelationship();

        $fromCustomer = $relationship->getFrom();

        if (CustomerRelationshipType::PARTNER_CONTACT_PERSON === $relationship->getType()->getValue()) {
            if (!\in_array(AccountCategory::PARTNER_CONTACT_PERSON, $fromCustomer->getCategories(), true)) {
                $fromCustomer->addCategory(AccountCategory::PARTNER_CONTACT_PERSON);
            }
        } elseif (CustomerRelationshipType::CONTACT_PERSON === $relationship->getType()->getValue()) {
            if (!\in_array(AccountCategory::CONTACT_PERSON, $fromCustomer->getCategories(), true)) {
                $fromCustomer->addCategory(AccountCategory::CONTACT_PERSON);
            }
        }
    }
}
