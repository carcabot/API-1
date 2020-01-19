<?php

declare(strict_types=1);

namespace App\Domain\Command\Ticket;

use App\Model\TicketNumberGenerator;

class UpdateTicketNumberHandler
{
    /**
     * @var TicketNumberGenerator
     */
    private $ticketNumberGenerator;

    /**
     * @param TicketNumberGenerator $ticketNumberGenerator
     */
    public function __construct(TicketNumberGenerator $ticketNumberGenerator)
    {
        $this->ticketNumberGenerator = $ticketNumberGenerator;
    }

    public function handle(UpdateTicketNumber $command): void
    {
        $ticket = $command->getTicket();
        $ticketNumber = $this->ticketNumberGenerator->generate($ticket);

        $ticket->setTicketNumber($ticketNumber);
    }
}
