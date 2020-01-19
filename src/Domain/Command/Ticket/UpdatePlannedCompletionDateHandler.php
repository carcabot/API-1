<?php

declare(strict_types=1);

namespace App\Domain\Command\Ticket;

use App\Model\TicketProcessor;
use Doctrine\ORM\EntityManagerInterface;

class UpdatePlannedCompletionDateHandler
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TicketProcessor
     */
    private $ticketProcessor;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TicketProcessor        $ticketProcessor
     */
    public function __construct(EntityManagerInterface $entityManager, TicketProcessor $ticketProcessor)
    {
        $this->entityManager = $entityManager;
        $this->ticketProcessor = $ticketProcessor;
    }

    public function handle(UpdatePlannedCompletionDate $command): void
    {
        $ticket = $command->getTicket();
        $sla = $command->getServiceLevelAgreement();

        $dateOpened = new \DateTime();

        $ticket->setDateOpened($dateOpened);

        $ticketPlannedCompletionDate = $this->ticketProcessor->getPlannedCompletionDate($ticket, $sla);
        $ticket->setPlannedCompletionDate($ticketPlannedCompletionDate);
    }
}
