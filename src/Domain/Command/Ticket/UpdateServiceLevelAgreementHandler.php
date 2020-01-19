<?php

declare(strict_types=1);

namespace App\Domain\Command\Ticket;

use App\Entity\ServiceLevelAgreement;
use App\Entity\TicketServiceLevelAgreement;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;

class UpdateServiceLevelAgreementHandler
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
    }

    public function handle(UpdateServiceLevelAgreement $command): void
    {
        $ticket = $command->getTicket();

        $qb = $this->entityManager->getRepository(ServiceLevelAgreement::class)->createQueryBuilder('sla');
        $expr = $qb->expr();
        $sla = null;

        $ticketCategory = $ticket->getCategory();
        $ticketPriority = $ticket->getPriority();
        $ticketSubcategory = $ticket->getSubcategory();
        $ticketType = $ticket->getType();

        $sla = $this->commandBus->handle(new GetMatchingServiceLevelAgreement($ticketCategory, $ticketSubcategory, $ticketPriority, $ticketType));

        if (null !== $sla) {
            // create the TicketServiceLevelAgreement and update the Ticket
            $ticketSla = new TicketServiceLevelAgreement();
            $ticketSla->setDescription($sla->getDescription());
            $ticketSla->setIsBasedOn($sla);
            $ticketSla->setName($sla->getName());
            $ticketSla->setTimer($sla->getTimer());

            foreach ($sla->getOperationExclusions() as $operationExclusion) {
                $ticketSla->addOperationExclusion(clone $operationExclusion);
            }

            $ticketSla->setTicket($ticket);
            $this->entityManager->persist($ticketSla);
            $this->entityManager->persist($ticket);
        }
    }
}
