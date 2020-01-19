<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\Ticket;

use App\Entity\Ticket;

/**
 * Builds the create task data for web service consumption.
 */
class BuildCreateTaskData
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
