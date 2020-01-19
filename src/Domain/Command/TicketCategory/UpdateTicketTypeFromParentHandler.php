<?php

declare(strict_types=1);

namespace App\Domain\Command\TicketCategory;

class UpdateTicketTypeFromParentHandler
{
    public function handle(UpdateTicketTypeFromParent $command)
    {
        $ticketCategory = $command->getTicketCategory();

        if (null !== $ticketCategory->getParent()) {
            $ticketCategory->clearTicketTypes();

            foreach ($ticketCategory->getParent()->getTicketTypes() as $ticketType) {
                $ticketCategory->addTicketType($ticketType);
            }
        }
    }
}
