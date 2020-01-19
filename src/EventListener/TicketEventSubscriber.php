<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Disque\JobType;
use App\Entity\Ticket;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TicketEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var DisqueQueue
     */
    private $mailerQueue;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @param DisqueQueue           $mailerQueue
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(DisqueQueue $mailerQueue, IriConverterInterface $iriConverter)
    {
        $this->mailerQueue = $mailerQueue;
        $this->iriConverter = $iriConverter;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['sendEmail', EventPriorities::POST_WRITE - 1],
            ],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function sendEmail(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof Ticket)) {
            return;
        }

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        /**
         * @var Ticket
         */
        $ticket = $controllerResult;

        $job = new DisqueJob([
            'data' => [
                'ticket' => $this->iriConverter->getIriFromItem($ticket),
            ],
            'type' => JobType::TICKET_CREATED,
            'ticket' => [
                '@id' => $this->iriConverter->getIriFromItem($ticket),
            ],
        ]);
        $this->mailerQueue->push($job);
    }
}
