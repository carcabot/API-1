<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\CustomerAccount;
use App\Entity\EmailActivity;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Ds\Map;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class CustomerAccountEmailActivityAdditionListener
{
    /**
     * @var DisqueQueue
     */
    private $disqueQueue;

    /**
     * @var Map<CustomerAccount, Activity>
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

        if (!$data instanceof CustomerAccount) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /**
         * @var CustomerAccount
         */
        $customerAccount = $data;

        $this->initialActivities->put($customerAccount, $customerAccount->getActivities());
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof CustomerAccount)) {
            return;
        }

        /**
         * @var CustomerAccount
         */
        $customerAccount = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        if (Request::METHOD_POST === $request->getMethod()) {
            $activities = $customerAccount->getActivities();
            foreach ($activities as $activity) {
                if ($activity instanceof EmailActivity) {
                    $this->pushToDisqueueQueue($activity);
                }
            }
        } elseif (Request::METHOD_PUT === $request->getMethod()) {
            $activities = $customerAccount->getActivities();
            $prevActivities = $this->initialActivities->get($customerAccount, null);

            foreach ($activities as $activity) {
                if (!\in_array($activity, $prevActivities, true) && $activity instanceof EmailActivity) {
                    $this->pushToDisqueueQueue($activity);
                }
            }
        }
    }

    private function pushToDisqueueQueue(EmailActivity $activity)
    {
        $job = new DisqueJob([
            'data' => [
                'activity' => $this->iriConverter->getIriFromItem($activity),
            ],
            'type' => JobType::CUSTOMER_ACCOUNT_EMAIL_ACTIVITY_CREATED,
            'activity' => [
                '@id' => $this->iriConverter->getIriFromItem($activity),
            ],
        ]);
        $this->disqueQueue->push($job);
    }
}
