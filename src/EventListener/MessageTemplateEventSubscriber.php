<?php

declare(strict_types=1);

namespace App\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\EventListener\EventPriorities;
use App\Disque\JobType;
use App\Entity\Message;
use App\Entity\MessageRecipientListItem;
use App\Entity\MessageTemplate;
use App\Entity\User;
use App\Enum\MessageStatus;
use App\Model\ReportGenerator;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class MessageTemplateEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ReportGenerator
     */
    private $reportGenerator;

    /**
     * @var DisqueQueue
     */
    private $messageQueue;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ReportGenerator        $reportGenerator
     * @param DisqueQueue            $messageQueue
     * @param IriConverterInterface  $iriConverter
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, ReportGenerator $reportGenerator, DisqueQueue $messageQueue, IriConverterInterface  $iriConverter, string $timezone)
    {
        $this->entityManager = $entityManager;
        $this->reportGenerator = $reportGenerator;
        $this->messageQueue = $messageQueue;
        $this->iriConverter = $iriConverter;
        $this->timezone = new \DateTimeZone($timezone);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['addMessageRecipients', EventPriorities::PRE_WRITE],
                ['queueMessage', EventPriorities::POST_WRITE],
            ],
        ];
    }

    public function addMessageRecipients(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof MessageTemplate)) {
            return;
        }

        /** @var MessageTemplate $messageTemplate */
        $messageTemplate = $controllerResult;

        if (empty($messageTemplate)) {
            throw new BadRequestHttpException('MessageTemplate not defined');
        }

        if (empty($messageTemplate->getPlannedStartDate())) {
            throw new BadRequestHttpException('When should we send this message?');
        }

        if (!(\in_array($request->getMethod(), [
            Request::METHOD_POST,
            Request::METHOD_PUT,
        ], true))) {
            return;
        }

        $recipients = [];

        if (!empty($messageTemplate->getRecipientsFilters())) {
            $recipients = $this->reportGenerator->createUserReport($messageTemplate->getRecipientsFilters(), true);
        } else {
            $recipients = $this->entityManager->getRepository(User::class)->findAll();
        }

        if (!empty($recipients)) {
            if (Request::METHOD_PUT === $request->getMethod()) {
                $messageTemplate->clearMessageRecipients();
            }

            /*
             * @var User
             */
            foreach ($recipients as $user) {
                if (empty($user->getExpoPushNotificationTokens())) {
                    continue;
                }

                $recipient = new MessageRecipientListItem();
                $message = new Message();

                $message->setMessageTemplate($messageTemplate);
                $message->setRecipient($recipient);
                $message->setStatus(new MessageStatus(MessageStatus::NEW));

                $recipient->setMessageAddress($user->getExpoPushNotificationTokens());
                $recipient->setCustomer($user->getCustomerAccount());
                $recipient->setMessage($message);

                $messageTemplate->addRecipient($recipient);
            }
        }
    }

    public function queueMessage(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $messageTemplate = $event->getControllerResult();

        if (!($messageTemplate instanceof MessageTemplate)) {
            return;
        }

        if (!(\in_array($request->getMethod(), [
            Request::METHOD_POST,
        ], true))) {
            return;
        }

        if (empty($messageTemplate->getRecipients()) || \count($messageTemplate->getRecipients()) < 1) {
            return;
        }

        $startDate = clone $messageTemplate->getPlannedStartDate();
        $startDate->setTimezone($this->timezone);
        $now = new \DateTime();
        $now->setTimezone($this->timezone);

        $daysDiff = (int) $startDate->diff($now)->format('%a');

        if (MessageStatus::NEW === $messageTemplate->getStatus()->getValue() && 0 === $daysDiff) {
            $newMessageJob = new DisqueJob([
                'data' => [
                    'messageTemplate' => $this->iriConverter->getIriFromItem($messageTemplate),
                    'date' => $messageTemplate->getPlannedStartDate()->format('c'),
                ],
                'type' => JobType::MESSAGE_EXECUTE_SCHEDULE,
            ]);

            $this->messageQueue->push($newMessageJob);
        }
    }
}
