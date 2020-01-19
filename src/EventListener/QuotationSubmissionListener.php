<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\EmailActivity;
use App\Entity\Quotation;
use App\Enum\QuotationStatus;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Map;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class QuotationSubmissionListener
{
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
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var Map<Quotation, string>
     */
    private $initialStatuses;

    /**
     * QuotationSubmissionListener constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param DisqueQueue            $applicationRequestQueue
     * @param DisqueQueue            $emailsQueue
     * @param IriConverterInterface  $iriConverter
     */
    public function __construct(EntityManagerInterface $entityManager, DisqueQueue $applicationRequestQueue, DisqueQueue $emailsQueue, IriConverterInterface $iriConverter)
    {
        $this->applicationRequestQueue = $applicationRequestQueue;
        $this->entityManager = $entityManager;
        $this->emailsQueue = $emailsQueue;
        $this->iriConverter = $iriConverter;
        $this->initialStatuses = new Map();
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $data = $request->attributes->get('data');

        if (!$data instanceof Quotation) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var Quotation $quotation */
        $quotation = $data;

        $this->initialStatuses->put($quotation, $quotation->getStatus()->getValue());
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof Quotation)) {
            return;
        }

        /** @var Quotation $quotation */
        $quotation = $controllerResult;

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        $initialStatus = $this->initialStatuses->get($quotation, null);

        if (QuotationStatus::COMPLETED === $initialStatus
            && QuotationStatus::SENT === $quotation->getStatus()->getValue()) {
            $quotationEmailActivity = new EmailActivity();
            if (null !== $quotation->getContactPerson() && null !== $quotation->getContactPerson()->getPersonDetails()) {
                $quotationEmailActivity->addToRecipient($quotation->getContactPerson()->getPersonDetails()->getContactPoints()[0]->getEmails()[0]);
            } elseif (null !== $quotation->getCorporationDetails()) {
                $quotationEmailActivity->addToRecipient($quotation->getCorporationDetails()->getContactPoints()[0]->getEmails()[0]);
            }

            if (null !== $quotation->getFile()) {
                $quotationEmailActivity->addMessageAttachment($quotation->getFile());
            }

            $job = new DisqueJob([
                'data' => [
                    'quotation' => $this->iriConverter->getIriFromItem($quotation),
                ],
                'type' => JobType::QUOTATION_SENT,
                'quotation' => [
                    '@id' => $this->iriConverter->getIriFromItem($quotation),
                ],
            ]);
            $this->emailsQueue->push($job);

            $this->entityManager->persist($quotation);
            $this->entityManager->flush();
        } elseif ((null === $initialStatus || QuotationStatus::DRAFT === $initialStatus)
            && QuotationStatus::COMPLETED === $quotation->getStatus()->getValue()) {
            try {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $this->iriConverter->getIriFromItem($quotation),
                    ],
                    'type' => JobType::QUOTATION_UPDATE_FILE,
                ]);
                $this->applicationRequestQueue->push($job);
            } catch (\Exception $e) {
                throw new BadRequestHttpException($e->getMessage(), $e);
            }
        }
    }
}
