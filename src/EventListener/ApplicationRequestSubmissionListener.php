<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Domain\Command\CustomerAccount\AddCustomerCategories;
use App\Domain\Command\User\AddRoleUser;
use App\Entity\ApplicationRequest;
use App\Entity\User;
use App\Enum\ApplicationRequestStatus;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Map;
use iter;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ApplicationRequestSubmissionListener
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
     * @var DisqueQueue
     */
    private $applicationRequestQueue;

    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var DisqueQueue
     */
    private $webServicesQueue;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Map<ApplicationRequest, string>
     */
    private $initialStatuses;

    /**
     * ApplicationRequestSubmissionListener constructor.
     *
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param DisqueQueue            $applicationRequestQueue
     * @param DisqueQueue            $emailsQueue
     * @param DisqueQueue            $webServicesQueue
     * @param IriConverterInterface  $iriConverter
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, DisqueQueue $applicationRequestQueue, DisqueQueue $emailsQueue, DisqueQueue $webServicesQueue, IriConverterInterface $iriConverter, TokenStorageInterface $tokenStorage)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->applicationRequestQueue = $applicationRequestQueue;
        $this->emailsQueue = $emailsQueue;
        $this->webServicesQueue = $webServicesQueue;
        $this->iriConverter = $iriConverter;
        $this->tokenStorage = $tokenStorage;
        $this->initialStatuses = new Map();
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
    public function onKernelViewPreWrite(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof ApplicationRequest)) {
            return;
        }

        /** @var ApplicationRequest $applicationRequest */
        $applicationRequest = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $submitter = null;

        if (null !== $token) {
            $authenticatedUser = $token->getUser();

            if ($authenticatedUser instanceof User) {
                $submitter = $authenticatedUser;
            }
        }

        $initialStatus = $this->initialStatuses->get($applicationRequest, null);

        if (null === $initialStatus || \in_array($initialStatus, [
                ApplicationRequestStatus::DRAFT,
                ApplicationRequestStatus::PARTNER_DRAFT,
            ], true)
        ) {
            if (ApplicationRequestStatus::IN_PROGRESS === $applicationRequest->getStatus()->getValue() && null !== $applicationRequest->getTariffRate()) {
                $tariffRate = clone $applicationRequest->getTariffRate();
                if (true === $tariffRate->getIsDailyRate()) {
                    $latestDailyRate = iter\reduce(function ($currentDailyRate, $dailyRate, $key) {
                        if (null === $currentDailyRate || $dailyRate->getDateModified() > $currentDailyRate->getDateModified()) {
                            return $dailyRate;
                        }

                        return $currentDailyRate;
                    }, $applicationRequest->getTariffRate()->getDailyRates(), null);

                    $tariffRate->removeAllDailyRates();
                    // assign the latest daily rate to the tariff
                    if (null !== $latestDailyRate) {
                        $newDailyRate = clone $latestDailyRate;
                        $newDailyRate->setTariffRate($tariffRate);
                        $tariffRate->addDailyRate($newDailyRate);
                    }
                }

                $tariffRate->setIsBasedOn($applicationRequest->getTariffRate());
                $applicationRequest->setDateSubmitted(new \DateTime());
                $applicationRequest->setSubmitter($submitter);
                $applicationRequest->setTariffRate($tariffRate);

                if (null !== $applicationRequest->getCustomer()) {
                    $customer = $this->commandBus->handle(new AddCustomerCategories($applicationRequest->getCustomer()));

                    if (null !== $customer && null !== $customer->getUser()) {
                        $this->commandBus->handle(new AddRoleUser($customer->getUser()));
                    }
                }

                if (null !== $applicationRequest->getLead()) {
                    $this->entityManager->remove($applicationRequest->getLead());
                }
            }

            if (ApplicationRequestStatus::IN_PROGRESS === $applicationRequest->getStatus()->getValue() && null !== $applicationRequest->getPromotion()) {
                $promotion = clone $applicationRequest->getPromotion();
                $promotion->setIsBasedOn($applicationRequest->getPromotion());

                $applicationRequest->setPromotion($promotion);
            }
        }
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

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        $initialStatus = $this->initialStatuses->get($applicationRequest, null);

        if (null === $initialStatus || \in_array($initialStatus, [
                ApplicationRequestStatus::DRAFT,
                ApplicationRequestStatus::PARTNER_DRAFT,
            ], true)
        ) {
            if (ApplicationRequestStatus::IN_PROGRESS === $applicationRequest->getStatus()->getValue()) {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $applicationRequest->getId(),
                    ],
                    'type' => JobType::APPLICATION_REQUEST_SUBMIT,
                ]);
                $this->webServicesQueue->push($job);

                $job = new DisqueJob([
                    'data' => [
                        'applicationRequest' => $this->iriConverter->getIriFromItem($applicationRequest),
                    ],
                    'type' => JobType::APPLICATION_REQUEST_SUBMITTED,
                    'applicationRequest' => [
                        '@id' => $this->iriConverter->getIriFromItem($applicationRequest),
                    ],
                ]);
                $this->emailsQueue->push($job);

                $job = new DisqueJob([
                    'data' => [
                        'customerAccountNumber' => null !== $applicationRequest->getCustomer() ? $applicationRequest->getCustomer()->getAccountNumber() : null,
                    ],
                    'type' => JobType::LEAD_CONVERT_STATUS,
                ]);

                $this->applicationRequestQueue->push($job);
            }
        }
    }
}
