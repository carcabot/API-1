<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Disque\JobType;
use App\Entity\Campaign;
use App\Enum\CampaignCategory;
use App\Enum\CampaignStatus;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Ds\Map;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DirectMailCampaignEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var DisqueQueue
     */
    private $campaignQueue;

    /**
     * @var Map<Campaign, array>
     */
    private $initialStatus;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param IriConverterInterface         $iriConverter
     * @param DisqueQueue                   $campaignQueue
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, IriConverterInterface $iriConverter, DisqueQueue $campaignQueue)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->iriConverter = $iriConverter;
        $this->campaignQueue = $campaignQueue;
        $this->initialStatus = new Map();
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequestPostRead', EventPriorities::POST_READ],
            ],
            KernelEvents::VIEW => [
                ['onPostWrite', EventPriorities::POST_WRITE - 1],
            ],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequestPostRead(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $data = $request->attributes->get('data');

        if (!$data instanceof Campaign) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var Campaign $campaign */
        $campaign = $data;

        if (CampaignCategory::DIRECT_MAIL !== $campaign->getCategory()->getValue()) {
            return;
        }

        $this->initialStatus->put($campaign, $campaign->getStatus()->getValue());
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onPostWrite(GetResponseForControllerResultEvent $event)
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

        if (CampaignCategory::DIRECT_MAIL !== $campaign->getCategory()->getValue()) {
            return;
        }

        $initialStatus = $this->initialStatus->get($campaign, null);

        if ($initialStatus !== $campaign->getStatus()->getValue() && CampaignStatus::EXECUTED === $campaign->getStatus()->getValue()) {
            $this->campaignQueue->push(new DisqueJob([
                'data' => [
                    'campaign' => $this->iriConverter->getIriFromItem($campaign),
                ],
                'type' => JobType::CAMPAIGN_EXECUTE,
            ]));
        }
    }
}
