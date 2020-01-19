<?php

declare(strict_types=1);

namespace App\Tests\Domain\Command\CustomerAccount;

use App\Domain\Command\CustomerAccount\UpdateRelationships;
use App\Domain\Command\CustomerAccount\UpdateRelationshipsHandler;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountRelationship;
use App\Enum\CustomerRelationshipType;
use PHPUnit\Framework\TestCase;

class UpdateRelationshipsTest extends TestCase
{
    public function testUpdateRelationships()
    {
        $fromAccountProphecy = $this->prophesize(CustomerAccount::class);
        $fromAccountProphecy->getRelationships()->willReturn([]);
        $fromAccount = $fromAccountProphecy->reveal();

        $toAccountProphecy = $this->prophesize(CustomerAccount::class);
        $toAccountProphecy->getRelationships()->willReturn([]);
        $toAccount = $toAccountProphecy->reveal();

        $relationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $relationshipProphecy->getFrom()->willReturn($fromAccount);
        $relationshipProphecy->getTo()->willReturn($toAccount);
        $relationship = $relationshipProphecy->reveal();

        $fromAccountProphecy->addRelationship($relationship)->shouldBeCalled();
        $toAccountProphecy->addRelationship($relationship)->shouldBeCalled();

        $updateRelationshipsHandler = new UpdateRelationshipsHandler();
        $updateRelationshipsHandler->handle(new UpdateRelationships($relationship));
    }

    public function testUpdateRelationshipExistingFrom()
    {
        $fromAccountProphecy = $this->prophesize(CustomerAccount::class);
        $fromAccount = $fromAccountProphecy->reveal();

        $toAccountProphecy = $this->prophesize(CustomerAccount::class);
        $toAccount = $toAccountProphecy->reveal();

        $relationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $relationshipProphecy->getType()->willReturn(new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON));
        $relationshipProphecy->getFrom()->willReturn($fromAccount);
        $relationshipProphecy->getTo()->willReturn($toAccount);
        $relationship = $relationshipProphecy->reveal();

        $fromAccountProphecy->getRelationships()->willReturn([$relationship]);
        $toAccountProphecy->getRelationships()->willReturn([]);
        $fromAccountProphecy->addRelationship($relationship)->shouldNotBeCalled();
        $toAccountProphecy->addRelationship($relationship)->shouldBeCalled();

        $updateRelationshipsHandler = new UpdateRelationshipsHandler();
        $updateRelationshipsHandler->handle(new UpdateRelationships($relationship));
    }

    public function testUpdateRelationshipExistingTo()
    {
        $fromAccountProphecy = $this->prophesize(CustomerAccount::class);
        $fromAccount = $fromAccountProphecy->reveal();

        $toAccountProphecy = $this->prophesize(CustomerAccount::class);
        $toAccount = $toAccountProphecy->reveal();

        $relationshipProphecy = $this->prophesize(CustomerAccountRelationship::class);
        $relationshipProphecy->getType()->willReturn(new CustomerRelationshipType(CustomerRelationshipType::CONTACT_PERSON));
        $relationshipProphecy->getFrom()->willReturn($fromAccount);
        $relationshipProphecy->getTo()->willReturn($toAccount);
        $relationship = $relationshipProphecy->reveal();

        $fromAccountProphecy->getRelationships()->willReturn([]);
        $toAccountProphecy->getRelationships()->willReturn([$relationship]);
        $fromAccountProphecy->addRelationship($relationship)->shouldBeCalled();
        $toAccountProphecy->addRelationship($relationship)->shouldNotBeCalled();

        $updateRelationshipsHandler = new UpdateRelationshipsHandler();
        $updateRelationshipsHandler->handle(new UpdateRelationships($relationship));
    }
}
