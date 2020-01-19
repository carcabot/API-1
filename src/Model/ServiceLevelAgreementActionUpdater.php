<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\QuantitativeValue;
use App\Entity\ServiceLevelAgreementAction;
use App\Entity\Ticket;
use App\Enum\TicketStatus;
use App\Service\DateTimeHelper;
use Doctrine\ORM\EntityManagerInterface;

class ServiceLevelAgreementActionUpdater
{
    /**
     * @var DateTimeHelper
     */
    private $dateTimeHelper;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WorkingHourCalculator
     */
    private $workingHourCalculator;

    /**
     * @param DateTimeHelper         $dateTimeHelper
     * @param EntityManagerInterface $entityManager
     * @param WorkingHourCalculator  $workingHourCalculator
     */
    public function __construct(DateTimeHelper $dateTimeHelper, EntityManagerInterface $entityManager, WorkingHourCalculator $workingHourCalculator)
    {
        $this->dateTimeHelper = $dateTimeHelper;
        $this->entityManager = $entityManager;
        $this->workingHourCalculator = $workingHourCalculator;
    }

    /**
     * @param Ticket      $ticket
     * @param string|null $initialStatus
     */
    public function generate(Ticket $ticket, ?string $initialStatus = null)
    {
        $serviceLevelAgreementAction = new ServiceLevelAgreementAction();
        $serviceLevelAgreementAction->setTicket($ticket);

        if (null !== $initialStatus) {
            $serviceLevelAgreementAction->setPreviousStatus(new TicketStatus($initialStatus));
            $serviceLevelAgreementAction->setStartTime(new \DateTime());
        } else {
            $serviceLevelAgreementAction->setStartTime(null !== $ticket->getDateOpened() ? $ticket->getDateOpened() : new \DateTime());
        }

        switch ($ticket->getStatus()->getValue()) {
            case TicketStatus::ASSIGNED:
                $serviceLevelAgreementAction->setStatus(new TicketStatus($ticket->getStatus()->getValue()));
                $serviceLevelAgreementAction->setDescription('Assigned');
                break;
            case TicketStatus::CANCELLED:
                $serviceLevelAgreementAction->setStatus(new TicketStatus($ticket->getStatus()->getValue()));
                $serviceLevelAgreementAction->setDescription('Cancelled');
                break;
            case TicketStatus::COMPLETED:
                $serviceLevelAgreementAction->setStatus(new TicketStatus($ticket->getStatus()->getValue()));
                $serviceLevelAgreementAction->setDescription('Completed');
                break;
            case TicketStatus::IN_PROGRESS:
                $serviceLevelAgreementAction->setStatus(new TicketStatus($ticket->getStatus()->getValue()));
                $serviceLevelAgreementAction->setDescription('In Progress');
                break;
            case TicketStatus::NEW:
                $serviceLevelAgreementAction->setStatus(new TicketStatus($ticket->getStatus()->getValue()));
                $serviceLevelAgreementAction->setDescription('New');
                break;
            case TicketStatus::PENDING_CUSTOMER_ACTION:
                $serviceLevelAgreementAction->setStatus(new TicketStatus($ticket->getStatus()->getValue()));
                $serviceLevelAgreementAction->setDescription('Pending Customer Action');
                break;
            case TicketStatus::PENDING_BILLING_TEAM:
                $serviceLevelAgreementAction->setStatus(new TicketStatus($ticket->getStatus()->getValue()));
                $serviceLevelAgreementAction->setDescription('Pending Billing Team');
                break;
            default:
                return;
        }

        $this->entityManager->persist($serviceLevelAgreementAction);
    }

    /**
     * @param Ticket $ticket
     * @param string $initialStatus
     */
    public function update(Ticket $ticket, string $initialStatus)
    {
        $qb = $this->entityManager->getRepository(ServiceLevelAgreementAction::class)->createQueryBuilder('action');
        $expr = $qb->expr();

        $openingHours = [];

        if (null !== $ticket->getServiceLevelAgreement()) {
            $openingHours = $ticket->getServiceLevelAgreement()->getOperationExclusions();
        }

        $slaActions = $qb->select('action')
            ->leftJoin('action.ticket', 'ticket')
            ->where($expr->eq('ticket.id', ':id'))
            ->andWhere($expr->isNull('action.value.value'))
            ->andWhere($expr->eq('action.status', ':status'))
            ->setParameters([
                'id' => $ticket->getId(),
                'status' => $initialStatus,
            ])
            ->orderBy('action.dateModified', 'DESC')
            ->getQuery()
            ->getResult();

        if (\count($slaActions) > 0) {
            $slaAction = \reset($slaActions);

            $endDate = new \DateTime();
            $slaAction->setEndTime($endDate);

            $diffMins = $this->workingHourCalculator->getWorkingMinutesFromDateRange($slaAction->getStartTime(), $endDate, $openingHours);

            $value = new QuantitativeValue((string) \round($diffMins, 2));
            $slaAction->setValue($value);

            $this->entityManager->persist($slaAction);
        }
    }
}
