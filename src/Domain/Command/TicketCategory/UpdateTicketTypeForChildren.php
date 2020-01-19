<?php

declare(strict_types=1);

namespace App\Domain\Command\TicketCategory;

use App\Entity\TicketCategory;

/**
 * Updates the ticket type for children categories.
 */
class UpdateTicketTypeForChildren
{
    /**
     * @var TicketCategory
     */
    private $ticketCategory;

    /**
     * @param TicketCategory $ticketCategory
     */
    public function __construct(TicketCategory $ticketCategory)
    {
        $this->ticketCategory = $ticketCategory;
    }

    /**
     * @return TicketCategory
     */
    public function getTicketCategory(): TicketCategory
    {
        return $this->ticketCategory;
    }
}
