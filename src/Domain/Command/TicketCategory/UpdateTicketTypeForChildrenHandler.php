<?php

declare(strict_types=1);

namespace App\Domain\Command\TicketCategory;

class UpdateTicketTypeForChildrenHandler
{
    public function handle(UpdateTicketTypeForChildren $command)
    {
        $ticketCategory = $command->getTicketCategory();

        foreach ($ticketCategory->getChildren() as $subcategory) {
            $subcategory->clearTicketTypes();

            foreach ($ticketCategory->getTicketTypes() as $ticketType) {
                $subcategory->addTicketType($ticketType);
            }
        }
    }
}
