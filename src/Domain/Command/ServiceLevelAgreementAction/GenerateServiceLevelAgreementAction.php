<?php

declare(strict_types=1);

namespace App\Domain\Command\ServiceLevelAgreementAction;

use App\Entity\Ticket;

class GenerateServiceLevelAgreementAction
{
    /**
     * @var Ticket
     */
    private $ticket;

    /**
     * @var string|null
     */
    private $initialStatus;

    /**
     * @param Ticket      $ticket
     * @param string|null $initialStatus
     */
    public function __construct(Ticket $ticket, ?string $initialStatus)
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
     * @return string|null
     */
    public function getInitialStatus(): ?string
    {
        return $this->initialStatus;
    }
}
