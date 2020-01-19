<?php

declare(strict_types=1);

namespace App\Domain\Command\Ticket;

use App\Entity\TicketCategory;
use App\Entity\TicketType;
use App\Enum\Priority;

class GetMatchingServiceLevelAgreement
{
    /**
     * @var TicketCategory
     */
    private $category;

    /**
     * @var TicketCategory
     */
    private $subcategory;

    /**
     * @var Priority
     */
    private $priority;

    /**
     * @var TicketType
     */
    private $type;

    /**
     * @param TicketCategory $category
     * @param TicketCategory $subcategory
     * @param Priority       $priority
     * @param TicketType     $type
     */
    public function __construct(TicketCategory $category, TicketCategory $subcategory, Priority $priority, TicketType $type)
    {
        $this->category = $category;
        $this->subcategory = $subcategory;
        $this->priority = $priority;
        $this->type = $type;
    }

    /**
     * Gets the category.
     *
     * @return TicketCategory
     */
    public function getCategory(): TicketCategory
    {
        return $this->category;
    }

    /**
     * Gets the subcategory.
     *
     * @return TicketCategory
     */
    public function getSubcategory(): TicketCategory
    {
        return $this->subcategory;
    }

    /**
     * Gets the priority.
     *
     * @return Priority
     */
    public function getPriority(): Priority
    {
        return $this->priority;
    }

    /**
     * Gets the type.
     *
     * @return TicketType
     */
    public function getType(): TicketType
    {
        return $this->type;
    }
}
