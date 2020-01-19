<?php

declare(strict_types=1);

namespace App\Domain\Command\ServiceLevelAgreementAction;

use App\Entity\Ticket;

class UpdateServiceLevelAgreementAction
{
    /**
     * @var Ticket
     */
    private $ticket;

    /**
     * @var string
     */
    private $initialStatus;

    /**
     * @param Ticket $ticket
     * @param string $initialStatus
     */
    public function __construct(Ticket $ticket, string $initialStatus)
    {
        $this->ticket = $ticket;
        $this->initialStatus = $initialStatus;
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

    /**
     * @return string
     */
    public function getInitialStatus(): string
    {
        return $this->initialStatus;
    }
}
