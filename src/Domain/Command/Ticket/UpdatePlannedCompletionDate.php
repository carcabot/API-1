<?php

declare(strict_types=1);

namespace App\Domain\Command\Ticket;

use App\Entity\Ticket;
use App\Entity\TicketServiceLevelAgreement;

class UpdatePlannedCompletionDate
{
    /**
     * @var Ticket
     */
    private $ticket;

    /**
     * @var TicketServiceLevelAgreement
     */
    private $serviceLevelAgreement;

    /**
     * @param Ticket                      $ticket
     * @param TicketServiceLevelAgreement $serviceLevelAgreement
     */
    public function __construct(Ticket $ticket, TicketServiceLevelAgreement $serviceLevelAgreement)
    {
        $this->ticket = $ticket;
        $this->serviceLevelAgreement = $serviceLevelAgreement;
    }

    /**
     * Gets the serviceLevelAgreement.
     *
     * @return TicketServiceLevelAgreement
     */
    public function getServiceLevelAgreement(): TicketServiceLevelAgreement
    {
        return $this->serviceLevelAgreement;
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
