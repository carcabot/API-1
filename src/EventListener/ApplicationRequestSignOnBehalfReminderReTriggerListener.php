<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\ApplicationRequest;
use App\Entity\EmailActivity;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Ds\Map;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ApplicationRequestSignOnBehalfReminderReTriggerListener
{
    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var Map<ApplicationRequest, int[]>
     */
    private $initialActivities;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @param DisqueQueue           $emailsQueue
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(DisqueQueue $emailsQueue, IriConverterInterface $iriConverter)
    {
        $this->initialActivities = new Map();
        $this->emailsQueue = $emailsQueue;
        $this->iriConverter = $iriConverter;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $data = $request->attributes->get('data');

        if (!$data instanceof ApplicationRequest) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $data;

        $emailActivities = [];

        foreach ($applicationRequest->getActivities() as $activity) {
            if ($activity instanceof EmailActivity) {
                $emailActivities[] = $activity->getId();
            }
        }
        $this->initialActivities->put($applicationRequest, $emailActivities);
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof ApplicationRequest)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $controllerResult;

        $initialEmailsActivities = $this->initialActivities->get($applicationRequest, null);

        if (!\in_array($request->getMethod(), [
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        foreach ($applicationRequest->getActivities() as $activity) {
            if ($activity instanceof EmailActivity) {
                if (!\in_array($activity->getId(), $initialEmailsActivities, true)) {
                    $this->pushToEmailQueue($applicationRequest);
                    break;
                }
            }
        }
    }

    private function pushToEmailQueue(ApplicationRequest $applicationRequest)
    {
        $job = new DisqueJob([
            'data' => [
                'applicationRequest' => $this->iriConverter->getIriFromItem($applicationRequest),
            ],
            'type' => JobType::APPLICATION_REQUEST_SUBMITTED_PENDING_AUTHORIZATION,
        ]);
        $this->emailsQueue->push($job);
    }
}
