<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

use App\Entity\CustomerAccountRelationship;

/**
 * Updates relationships.
 */
class UpdateRelationships
{
    /**
     * @var CustomerAccountRelationship
     */
    private $relationship;

    /**
     * @param CustomerAccountRelationship $relationship
     */
    public function __construct(CustomerAccountRelationship $relationship)
    {
        $this->relationship = $relationship;
    }

    /**
     * Gets the relationship.
     *
     * @return CustomerAccountRelationship
     */
    public function getRelationship(): CustomerAccountRelationship
    {
        return $this->relationship;
    }
}
