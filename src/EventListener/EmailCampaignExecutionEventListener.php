<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Campaign;
use App\Enum\CampaignCategory;
use App\Enum\CampaignStatus;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Map;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class EmailCampaignExecutionEventListener
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
     * @var Map<Campaign, string>
     */
    private $initialStatuses;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param DisqueQueue            $campaignQueue
     * @param string                 $timezone
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, DisqueQueue $campaignQueue, string $timezone)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->campaignQueue = $campaignQueue;
        $this->timezone = new \DateTimeZone($timezone);
        $this->initialStatuses = new Map();
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
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

        if (CampaignCategory::EMAIL !== $campaign->getCategory()->getValue()) {
            return;
        }

        $this->initialStatuses->put($campaign, $campaign->getStatus()->getValue());
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

        if (CampaignCategory::EMAIL !== $campaign->getCategory()->getValue()) {
            return;
        }

        $initialStatus = $this->initialStatuses->get($campaign, null);

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
