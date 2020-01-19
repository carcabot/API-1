<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Disque\JobType;
use App\Entity\Lead;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Ds\Map;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LeadAssignedEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var Map<Lead, string>
     */
    private $initialAssignees;

    /**
     * @param DisqueQueue           $emailsQueue
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(DisqueQueue $emailsQueue, IriConverterInterface $iriConverter)
    {
        $this->emailsQueue = $emailsQueue;
        $this->iriConverter = $iriConverter;
        $this->initialAssignees = new Map();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', EventPriorities::POST_READ],
            ],
            KernelEvents::VIEW => [
                ['sendEmailNotification', EventPriorities::POST_WRITE],
            ],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $data = $request->attributes->get('data');

        if (!$data instanceof Lead) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var Lead $lead */
        $lead = $data;

        $this->initialAssignees->put($lead, $lead->getAssignee());
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function sendEmailNotification(GetResponseForControllerResultEvent $event)
    {
        $lead = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$lead instanceof Lead) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        $initialAssignee = $this->initialAssignees->get($lead, null);

        if (null !== $lead->getAssignee() && $initialAssignee !== $lead->getAssignee()) {
            $job = new DisqueJob([
                'data' => [
                    'lead' => $this->iriConverter->getIriFromItem($lead),
                ],
                'type' => JobType::LEAD_ASSIGNED_ASSIGNEE,
                'lead' => [
                    '@id' => $this->iriConverter->getIriFromItem($lead),
                ],
            ]);
            $this->emailsQueue->push($job);
        }
    }
}
