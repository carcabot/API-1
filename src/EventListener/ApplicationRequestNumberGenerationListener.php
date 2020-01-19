<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestNumber;
use App\Domain\Command\ApplicationRequest\UpdateApplicationRequestToken;
use App\Domain\Command\ApplicationRequest\UpdateEmailActivity;
use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Map;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ApplicationRequestNumberGenerationListener
{
    use Traits\RunningNumberLockTrait;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var Map<ApplicationRequest, string>
     */
    private $initialStatuses;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var bool
     */
    private $sendEmail;

    /**
     * @param CommandBus             $commandBus
     * @param DisqueQueue            $emailsQueue
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     */
    public function __construct(CommandBus $commandBus, DisqueQueue $emailsQueue, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter)
    {
        $this->initialStatuses = new Map();
        $this->commandBus = $commandBus;
        $this->emailsQueue = $emailsQueue;
        $this->iriConverter = $iriConverter;
        $this->setEntityManager($entityManager);
        $this->setLocked(false);
        $this->sendEmail = false;
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
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $data;

        $this->initialStatuses->put($applicationRequest, $applicationRequest->getStatus()->getValue());
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

        $initialStatus = $this->initialStatuses->get($applicationRequest, null);

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        if (\in_array($applicationRequest->getStatus()->getValue(), [
                ApplicationRequestStatus::DRAFT,
                ApplicationRequestStatus::IN_PROGRESS,
                ApplicationRequestStatus::PARTNER_DRAFT,
                ApplicationRequestStatus::PENDING,
            ], true) &&
            $initialStatus !== $applicationRequest->getStatus()->getValue()
        ) {
            if (null === $initialStatus ||
                (ApplicationRequestStatus::IN_PROGRESS === $applicationRequest->getStatus()->getValue() &&
                    (ApplicationRequestStatus::DRAFT === $initialStatus
                        || ApplicationRequestStatus::PARTNER_DRAFT === $initialStatus
                        || ApplicationRequestStatus::PENDING === $initialStatus))
            ) {
                $this->startLockTransaction();
                $this->commandBus->handle(new UpdateApplicationRequestNumber($applicationRequest));
            }

            if ((null === $initialStatus && ApplicationRequestStatus::PENDING === $applicationRequest->getStatus()->getValue())
                || ($initialStatus !== $applicationRequest->getStatus()->getValue() && ApplicationRequestStatus::PENDING === $applicationRequest->getStatus()->getValue())
            ) {
                $this->commandBus->handle(new UpdateApplicationRequestToken($applicationRequest));
                $this->commandBus->handle(new UpdateEmailActivity($applicationRequest));
                $this->sendEmail = true;
            }
        }
    }

    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();

        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof ApplicationRequest)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $controllerResult;

        if ($this->sendEmail) {
            $job = new DisqueJob([
                'data' => [
                    'applicationRequest' => $this->iriConverter->getIriFromItem($applicationRequest),
                ],
                'type' => JobType::APPLICATION_REQUEST_SUBMITTED_PENDING_AUTHORIZATION,
            ]);
            $this->emailsQueue->push($job);
        }
    }
}
