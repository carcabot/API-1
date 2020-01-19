<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Lead;
use App\Entity\SmsActivity;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Ds\Map;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class LeadSMSActivityAdditionListener
{
    /**
     * @var DisqueQueue
     */
    private $disqueQueue;

    /**
     * @var Map<Lead, Activity>
     */
    private $initialActivities;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @param DisqueQueue           $disqueQueue
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(DisqueQueue $disqueQueue, IriConverterInterface $iriConverter)
    {
        $this->disqueQueue = $disqueQueue;
        $this->initialActivities = new Map();
        $this->iriConverter = $iriConverter;
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

        /**
         * @var Lead
         */
        $lead = $data;

        $activities = [];

        foreach ($lead->getActivities() as $activity) {
            $activities[] = $activity->getId();
        }

        $this->initialActivities->put($lead, $activities);
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof Lead)) {
            return;
        }

        /**
         * @var Lead
         */
        $lead = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        if (Request::METHOD_POST === $request->getMethod()) {
            $activities = $lead->getActivities();
            foreach ($activities as $activity) {
                if ($activity instanceof SmsActivity) {
                    $this->pushToDisqueueQueue($activity);
                }
            }
        } elseif (Request::METHOD_PUT === $request->getMethod()) {
            $activities = $lead->getActivities();
            $prevActivities = $this->initialActivities->get($lead, []);

            foreach ($activities as $activity) {
                if (!\in_array($activity->getId(), $prevActivities, true) && $activity instanceof SmsActivity) {
                    $this->pushToDisqueueQueue($activity);
                }
            }
        }
    }

    private function pushToDisqueueQueue(SmsActivity $activity)
    {
        $job = new DisqueJob([
            'data' => [
                'id' => $activity->getId(),
            ],
            'type' => JobType::LEAD_SMS_CUSTOMER_SERVICE_FEEDBACK_ACTIVITY_CREATED,
        ]);
        $this->disqueQueue->push($job);
    }
}
