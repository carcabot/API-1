<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\QuantitativeValue;
use App\Entity\ServiceLevelAgreementAction;
use App\Entity\Ticket;
use App\Entity\TicketServiceLevelAgreement;
use App\Enum\TicketStatus;
use App\Enum\TimeType;
use Doctrine\ORM\EntityManagerInterface;

class ServiceLevelAgreementTimerCalculator
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WorkingHourCalculator
     */
    private $workingHourCalculator;

    /**
     * @param EntityManagerInterface $entityManager
     * @param WorkingHourCalculator  $workingHourCalculator
     */
    public function __construct(EntityManagerInterface $entityManager, WorkingHourCalculator $workingHourCalculator)
    {
        $this->entityManager = $entityManager;
        $this->workingHourCalculator = $workingHourCalculator;
    }

    /**
     * @param Ticket $ticket
     *
     * @return array
     */
    public function calculate(Ticket $ticket)
    {
        $em = $this->entityManager;
        $expr = $em->getExpressionBuilder();
        $qb = $em->getRepository(ServiceLevelAgreementAction::class)->createQueryBuilder('sla');
        $minutesLeft = null;
        $paused = false;
        $timer = null;

        $sla = $ticket->getServiceLevelAgreement();
        $ticketDateOpened = $ticket->getDateOpened();
        $ticketDateClosed = $ticket->getDateClosed();
        $ticketStatus = $ticket->getStatus()->getValue();
        $slaActions = $ticket->getServiceLevelAgreementActions();

        if (null !== $sla && null !== $sla->getTimer()->getValue()) {
            $timer = (float) \round($this->getMinutesFromDuration($sla->getTimer()), 2);

            if (null !== $ticketDateClosed && null !== $ticketDateOpened) {
                $minutesLeft = $this->calculateMinutesLeft($sla, $slaActions, $ticketDateOpened, $ticketDateClosed);
                $paused = true;
            } elseif (null !== $ticketDateOpened) {
                $minutesLeft = $this->calculateMinutesLeft($sla, $slaActions, $ticketDateOpened, new \DateTime());
                $paused = $this->workingHourCalculator->isTimerPaused($ticket);
            }
        }

        $ticket->setTimer(new QuantitativeValue(null !== $timer ? (string) $timer : null, null, null, TimeType::MIN));
        $ticket->setTimeLeft(new QuantitativeValue(null !== $minutesLeft ? (string) \round($minutesLeft, 2) : null, null, null, TimeType::MIN));
        $ticket->setPaused($paused);

        //@todo idk if this is good idea, but keep it like this in order not to break other codes
        return [
            'timer' => $timer,
            'timeLeft' => $minutesLeft,
            'paused' => $paused,
        ];
    }

    /**
     * @param TicketServiceLevelAgreement $sla
     * @param array                       $slaActions
     * @param \DateTime                   $start
     * @param \DateTime                   $end
     *
     * @return array
     */
    private function calculateMinutesLeft(TicketServiceLevelAgreement $sla, array $slaActions, \DateTime $start, \DateTime $end)
    {
        $minutesLeft = $this->getMinutesFromDuration($sla->getTimer());
        $begin = clone $start;

        if (0 !== $minutesLeft) {
            if (\count($slaActions) > 0) {
                $workingMinutes = 0;

                foreach ($slaActions as $action) {
                    // @todo better logic handling
                    if (null === $action->getValue()->getValue()) {
                        if (\in_array($action->getStatus()->getValue(), [
                                TicketStatus::ASSIGNED,
                                TicketStatus::IN_PROGRESS,
                            ], true)) {
                            $workingMinutes += $this->workingHourCalculator->getWorkingMinutesFromDateRange($action->getStartTime(), new \DateTime(), $sla->getOperationExclusions());
                        }
                    } elseif (\in_array($action->getStatus()->getValue(), [
                        TicketStatus::ASSIGNED,
                        TicketStatus::IN_PROGRESS,
                    ], true)) {
                        $workingMinutes += $action->getValue()->getValue();
                    }/* elseif (\in_array($action->getStatus()->getValue(), [
                        TicketStatus::PENDING_BILLING_TEAM,
                        TicketStatus::PENDING_CUSTOMER_ACTION,
                    ], true)) {
                        $workingMinutes -= $action->getValue()->getValue();
                    }*/
                }
                $minutesLeft -= $workingMinutes;
            } else {
                // @todo not needed??? need to write tests.
                $workingMinutes = $this->workingHourCalculator->getWorkingMinutesFromDateRange($begin, $end, $sla->getOperationExclusions());
                $minutesLeft -= $workingMinutes;
            }
        }

        return $minutesLeft;
    }

    private function getMinutesFromDuration(QuantitativeValue $duration)
    {
        if (null === $duration->getValue()) {
            return 0;
        }

        // default is hours
        switch ($duration->getUnitCode()) {
            case 'DAY':
                return (int) ($duration->getValue() * 24 * 60);
                break;
            case 'MIN':
                return (int) $duration->getValue();
                break;
            default:
                return (int) ($duration->getValue() * 60);
        }
    }
}
