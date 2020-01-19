<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Domain\Command\PartnerCommissionStatement\UpdateEndDate;
use App\Entity\Partner;
use App\Entity\PartnerCommissionStatement;
use App\Enum\CommissionStatementStatus;
use App\Model\PartnerCommissionProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Map;
use League\Tactician\CommandBus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class PartnerEventSubscriber implements EventSubscriberInterface
{
    use Traits\RunningNumberLockTrait;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var PartnerCommissionProcessor
     */
    private $partnerCommissionProcessor;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var PartnerCommissionStatement|null
     */
    private $commissionStatement;

    /**
     * @var Map<Partner, array>
     */
    private $initialPayoutDates;

    /**
     * @param CommandBus                 $commandBus
     * @param EntityManagerInterface     $entityManager
     * @param IriConverterInterface      $iriConverter
     * @param PartnerCommissionProcessor $partnerCommissionProcessor
     * @param string                     $timezone
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, PartnerCommissionProcessor $partnerCommissionProcessor, string $timezone)
    {
        $this->commandBus = $commandBus;
        $this->iriConverter = $iriConverter;
        $this->partnerCommissionProcessor = $partnerCommissionProcessor;
        $this->timezone = new \DateTimeZone($timezone);
        $this->commissionStatement = null;
        $this->initialPayoutDates = new Map();
        $this->setEntityManager($entityManager);
        $this->setLocked(false);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequestPostRead', EventPriorities::POST_READ - 1],
            ],
            KernelEvents::VIEW => [
                ['validatePayoutScheduleTime', EventPriorities::PRE_WRITE + 1],
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

        if (!$data instanceof Partner) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var Partner $partner */
        $partner = $data;

        if (null !== $partner->getPayoutStartDate()) {
            $initialPayoutDate = clone $partner->getPayoutStartDate();
        } else {
            $initialPayoutDate = null;
        }

        $this->initialPayoutDates->put($partner, $initialPayoutDate);
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function validatePayoutScheduleTime(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if (!$controllerResult instanceof Partner) {
            return;
        }

        if (!\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true)) {
            return;
        }

        /** @var Partner $partner */
        $partner = $controllerResult;

        $payoutStartDate = null;
        $initialPayoutDate = $this->initialPayoutDates->get($partner, null);

        if (null === $partner->getPayoutCycle()->getValue() || null === $partner->getPayoutCycle()->getUnitCode() || null === $partner->getPayoutStartDate()) {
            return;
        }

        if ($initialPayoutDate !== $partner->getPayoutStartDate()) {
            $payoutStartDate = new \DateTime($partner->getPayoutStartDate()->format('Y-m-d'));
            $payoutStartDate->setTimezone($this->timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));
        }

        if (null !== $payoutStartDate) {
            $now = new \DateTime();

            if ($now >= $payoutStartDate) {
                throw new BadRequestHttpException('Payout date must be a future date.');
            }

            // Find for an existing PartnerCommissionStatement with status = 'NEW'
            $this->commissionStatement = $this->entityManager->getRepository(PartnerCommissionStatement::class)->findOneBy([
                'partner' => $partner->getId(),
                'status' => new CommissionStatementStatus(CommissionStatementStatus::NEW),
            ]);

            if (null !== $this->commissionStatement) {
                $this->commandBus->handle(new UpdateEndDate($this->commissionStatement, $payoutStartDate, $this->timezone));
            } else {
                $this->startLockTransaction();
                $this->commissionStatement = $this->partnerCommissionProcessor->createNewStatement($partner, $payoutStartDate);
                $this->entityManager->persist($this->commissionStatement);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();

        if (null !== $this->commissionStatement) {
            $this->partnerCommissionProcessor->scheduleGenerateJob($this->commissionStatement);
        }
    }
}
