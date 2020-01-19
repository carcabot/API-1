<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\CustomerAccount;

use App\Domain\Command\CustomerAccount\UpdateCategories;
use App\Domain\Command\CustomerAccount\UpdateCategoriesHandler;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountRelationship;
use App\Enum\AccountCategory;
use App\Enum\CustomerRelationshipType;
use PHPUnit\Framework\TestCase;

class UpdateCategoriesTest extends TestCase
{
    public function testUpdateContactPersonCategory()
    {
        $fromAccountProphecy = $this->prophesize(CustomerAccount::class);
        $fromAccountProphecy->getRelationships()->willReturn([]);
        $fromAccountProphecy->getCategories()->willReturn([]);
        $fromAccount = $fromAccountProphecy->reveal();

        $toAccountProphecy = $this->prophesize(CustomerAccount::class);
        $toAccountProphecy->getRelationships()->willReturn([]);
        $toAccount = $toAccountProphecy->reveal();

        $relationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $relationshipProphecy->getFrom()->willReturn($fromAccount);
        $relationshipProphecy->getTo()->willReturn($toAccount);
        $relationshipProphecy->getType()->willReturn(new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON));
        $relationship = $relationshipProphecy->reveal();

        $fromAccountProphecy->addCategory(AccountCategory::CONTACT_PERSON)->shouldBeCalled();
        $fromAccountProphecy->addCategory(AccountCategory::PARTNER_CONTACT_PERSON)->shouldNotBeCalled();

        $updateCategoriesHandler = new UpdateCategoriesHandler();
        $updateCategoriesHandler->handle(new UpdateCategories($relationship));
    }

    public function testUpdateExistingContactPersonCategory()
    {
        $fromAccountProphecy = $this->prophesize(CustomerAccount::class);
        $fromAccountProphecy->getRelationships()->willReturn([]);
        $fromAccountProphecy->getCategories()->willReturn([AccountCategory::CONTACT_PERSON]);
        $fromAccount = $fromAccountProphecy->reveal();

        $toAccountProphecy = $this->prophesize(CustomerAccount::class);
        $toAccountProphecy->getRelationships()->willReturn([]);
        $toAccount = $toAccountProphecy->reveal();

        $relationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $relationshipProphecy->getFrom()->willReturn($fromAccount);
        $relationshipProphecy->getTo()->willReturn($toAccount);
        $relationshipProphecy->getType()->willReturn(new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON));
        $relationship = $relationshipProphecy->reveal();

        $fromAccountProphecy->addCategory(AccountCategory::CONTACT_PERSON)->shouldNotBeCalled();
        $fromAccountProphecy->addCategory(AccountCategory::PARTNER_CONTACT_PERSON)->shouldNotBeCalled();

        $updateCategoriesHandler = new UpdateCategoriesHandler();
        $updateCategoriesHandler->handle(new UpdateCategories($relationship));
    }

    public function testUpdatePartnerContactPersonCategory()
    {
        $fromAccountProphecy = $this->prophesize(CustomerAccount::class);
        $fromAccountProphecy->getRelationships()->willReturn([]);
        $fromAccountProphecy->getCategories()->willReturn([]);
        $fromAccount = $fromAccountProphecy->reveal();

        $toAccountProphecy = $this->prophesize(CustomerAccount::class);
        $toAccountProphecy->getRelationships()->willReturn([]);
        $toAccount = $toAccountProphecy->reveal();

        $relationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $relationshipProphecy->getFrom()->willReturn($fromAccount);
        $relationshipProphecy->getTo()->willReturn($toAccount);
        $relationshipProphecy->getType()->willReturn(new CustomerRelationshipType(CustomerRelationshipType::PARTNER_CONTACT_PERSON));
        $relationship = $relationshipProphecy->reveal();

        $fromAccountProphecy->addCategory(AccountCategory::CONTACT_PERSON)->shouldNotBeCalled();
        $fromAccountProphecy->addCategory(AccountCategory::PARTNER_CONTACT_PERSON)->shouldBeCalled();

        $updateCategoriesHandler = new UpdateCategoriesHandler();
        $updateCategoriesHandler->handle(new UpdateCategories($relationship));
    }

    public function testUpdateExistingPartnerContactPersonCategory()
    {
        $fromAccountProphecy = $this->prophesize(CustomerAccount::class);
        $fromAccountProphecy->getRelationships()->willReturn([]);
        $fromAccountProphecy->getCategories()->willReturn([AccountCategory::PARTNER_CONTACT_PERSON]);
        $fromAccount = $fromAccountProphecy->reveal();

        $toAccountProphecy = $this->prophesize(CustomerAccount::class);
        $toAccountProphecy->getRelationships()->willReturn([]);
        $toAccount = $toAccountProphecy->reveal();

        $relationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $relationshipProphecy->getFrom()->willReturn($fromAccount);
        $relationshipProphecy->getTo()->willReturn($toAccount);
        $relationshipProphecy->getType()->willReturn(new CustomerRelationshipType(CustomerRelationshipType::PARTNER_CONTACT_PERSON));
        $relationship = $relationshipProphecy->reveal();

        $fromAccountProphecy->addCategory(AccountCategory::CONTACT_PERSON)->shouldNotBeCalled();
        $fromAccountProphecy->addCategory(AccountCategory::PARTNER_CONTACT_PERSON)->shouldNotBeCalled();

        $updateCategoriesHandler = new UpdateCategoriesHandler();
        $updateCategoriesHandler->handle(new UpdateCategories($relationship));
    }
}
