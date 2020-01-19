<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Command\ServiceLevelAgreementAction\GenerateServiceLevelAgreementAction;
use App\Domain\Command\ServiceLevelAgreementAction\UpdateServiceLevelAgreementAction;
use App\Domain\Command\Ticket\UpdatePlannedCompletionDate;
use App\Domain\Command\Ticket\UpdateServiceLevelAgreement;
use App\Entity\Ticket;
use App\Enum\TicketStatus;
use App\Model\ServiceLevelAgreementTimerCalculator;
use Ds\Map;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class TicketStatusUpdaterListener
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var Map<Ticket, string>
     */
    private $initialStatuses;

    /**
     * @var ServiceLevelAgreementTimerCalculator
     */
    private $serviceLevelAgreementTimerCalculator;

    /**
     * @param CommandBus                           $commandBus
     * @param ServiceLevelAgreementTimerCalculator $serviceLevelAgreementTimerCalculator
     */
    public function __construct(CommandBus $commandBus, ServiceLevelAgreementTimerCalculator $serviceLevelAgreementTimerCalculator)
    {
        $this->commandBus = $commandBus;
        $this->serviceLevelAgreementTimerCalculator = $serviceLevelAgreementTimerCalculator;
        $this->initialStatuses = new Map();
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $data = $request->attributes->get('data');

        if (!$data instanceof Ticket) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_PUT,
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        /**
         * @var Ticket
         */
        $ticket = $data;

        $this->initialStatuses->put($ticket, $ticket->getStatus()->getValue());
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelViewPreWrite(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof Ticket)) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_PUT,
            Request::METHOD_POST,
        ], true)) {
            return;
        }

        /**
         * @var Ticket
         */
        $ticket = $controllerResult;
        $initialStatus = $this->initialStatuses->get($ticket, null);

        // before any sla action magic, we need to determine the correct sla for the ticket.
        // @todo config table to determine when to start the sla timer
        if (\in_array($ticket->getStatus()->getValue(), [
                TicketStatus::ASSIGNED,
                TicketStatus::IN_PROGRESS,
            ], true) && null === $ticket->getServiceLevelAgreement()
        ) {
            $this->commandBus->handle(new UpdateServiceLevelAgreement($ticket));

            // should be done only once and just after sla is assigned to the ticket
            if (null !== $ticket->getServiceLevelAgreement()) {
                $this->commandBus->handle(new UpdatePlannedCompletionDate($ticket, $ticket->getServiceLevelAgreement()));
            }
        }

        if (null !== $ticket->getServiceLevelAgreement()) {
            // update if initialStatus is not null, for existing sla action if any.
            if (null !== $initialStatus && $initialStatus !== $ticket->getStatus()->getValue()) {
                $this->commandBus->handle(new UpdateServiceLevelAgreementAction($ticket, $initialStatus));
            }

            // generate new sla action if initialStatus is null (POST), OR status has changed (PUT).
            if (null === $initialStatus || $initialStatus !== $ticket->getStatus()->getValue()) {
                $this->commandBus->handle(new GenerateServiceLevelAgreementAction($ticket, $initialStatus));
            }
        }

        if (null !== $initialStatus && $initialStatus !== $ticket->getStatus()->getValue() &&
            (TicketStatus::CANCELLED === $ticket->getStatus()->getValue() || TicketStatus::COMPLETED === $ticket->getStatus()->getValue())) {
            $ticket->setDateClosed(new \DateTime());
            $this->serviceLevelAgreementTimerCalculator->calculate($ticket);
        }
    }
}
