<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Campaign;
use App\Enum\CampaignStatus;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class EmailCampaignEndEventListener
{
    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var DisqueQueue
     */
    private $campaignQueue;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param IriConverterInterface $iriConverter
     * @param DisqueQueue           $campaignQueue
     * @param string                $timezone
     */
    public function __construct(IriConverterInterface $iriConverter, DisqueQueue $campaignQueue, string $timezone)
    {
        $this->iriConverter = $iriConverter;
        $this->campaignQueue = $campaignQueue;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof Campaign)) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var Campaign $campaign */
        $campaign = $controllerResult;

        if (null !== $campaign->getEndDate() && \in_array($campaign->getStatus()->getValue(), [
            CampaignStatus::EXECUTED,
            CampaignStatus::SCHEDULED,
        ], true)) {
            $endDate = clone $campaign->getEndDate();
            $endDate->setTimezone($this->timezone);
            $now = new \DateTime();
            $now->setTimezone($this->timezone);

            $daysDiff = (int) $endDate->diff($now)->format('%a');

            // solves the same day problem
            // if difference in end date with today is more than 1 day, it will be picked up by the daily campaign cron worker
            if (0 === $daysDiff) {
                $this->campaignQueue->schedule(new DisqueJob([
                    'data' => [
                        'campaign' => $this->iriConverter->getIriFromItem($campaign),
                        'endDate' => $campaign->getEndDate()->format('c'),
                    ],
                    'type' => JobType::CAMPAIGN_END,
                ]), $campaign->getEndDate());
            }
        }
    }
}
