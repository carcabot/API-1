<?php

declare(strict_types=1);

namespace App\Domain\Command\Ticket;

use App\Entity\Ticket;

/**
 * Updates ticket number.
 */
class UpdateTicketNumber
{
    /**
     * @var Ticket
     */
    private $ticket;

    /**
     * @param Ticket $ticket
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Gets the ticket.
     *
     * @return Ticket
     */
    public function getTicket(): Ticket
    {
        return $this->ticket;
    }
}
