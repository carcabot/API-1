<?php

declare(strict_types=1);

namespace App\Command;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\MessageRecipientListItem;
use App\Entity\MessageTemplate;
use App\Enum\MessageStatus;
use App\Enum\MessageType;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use Psr\Log\LoggerInterface;
use Solvecrew\ExpoNotificationsBundle\Manager\NotificationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessagesWorkerStartCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $emergencyWebhookUrl;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DisqueQueue
     */
    private $messageQueue;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var string
     */
    private $memoryLimit;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param LoggerInterface        $logger
     * @param DisqueQueue            $messageQueue
     * @param string                 $timezone
     * @param string                 $emergencyWebhookUrl
     * @param string                 $memoryLimit
     * @param ValidatorInterface     $validator
     * @param NotificationManager    $notificationManager
     */
    public function __construct(EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, LoggerInterface $logger, DisqueQueue $messageQueue, string $timezone, string $emergencyWebhookUrl, string $memoryLimit, ValidatorInterface $validator, NotificationManager $notificationManager)
    {
        parent::__construct();

        $this->emergencyWebhookUrl = $emergencyWebhookUrl;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->logger = $logger;
        $this->messageQueue = $messageQueue;
        $this->timezone = new \DateTimeZone($timezone);
        $this->memoryLimit = $memoryLimit;
        $this->validator = $validator;
        $this->notificationManager = $notificationManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:messages-worker:start')
            ->setDescription('Starts the messages worker.')
            ->setHelp(<<<'EOF'
The %command.name% command starts the messages worker.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $io->success('Worker ready to accept tasks in the messages queue.');
        $io->comment('Stop the worker with CONTROL-C.');

        while ($job = $this->messageQueue->pull()) {
            if (!$job instanceof DisqueJob) {
                throw new \UnexpectedValueException();
            }
            $task = $job->getBody();

            if ($job->getNacks() > 0 || $job->getAdditionalDeliveries() > 0) {
                $this->messageQueue->processed($job);

                $io->text(\sprintf('Failed JOB %s.', $job->getId()));
                $io->text(\json_encode($task, JSON_PRETTY_PRINT));
                $this->logger->info('Failed JOB '.\json_encode($task, JSON_PRETTY_PRINT));

                $botData = [
                    'job' => $job,
                    'task' => $task,
                ];

                $this->sendToBot($botData);

                $io->newLine();

                continue;
            }

            $error = false;
            $endMessageLog = 'You should not see this message.';
            $errorMessageLog = 'You should not see this error message.';
            $utcTimezone = new \DateTimeZone('UTC');

            //log start of job
            $io->text(\sprintf('[%s] Running JOB %s.', (new \DateTime())->format('r'), $job->getId()));
            $io->text(\json_encode($task, JSON_PRETTY_PRINT));
            $this->logger->info('Running JOB '.\json_encode($task, JSON_PRETTY_PRINT));

            switch ($task['type']) {
                case JobType::MESSAGE_EXECUTE:
                    try {
                        //run the function
                        $messageTemplate = null;
                        $errorCount = 0;

                        try {
                            $messageTemplate = $this->iriConverter->getItemFromIri($task['data']['messageTemplate']);
                        } catch (\Exception $e) {
                            $io->error($e->getMessage());
                            $this->logger->error($e->getMessage());
                        }

                        if (null !== $messageTemplate && $messageTemplate instanceof MessageTemplate
                                && \in_array($messageTemplate->getStatus()->getValue(), [
                                    MessageStatus::NEW, MessageStatus::SCHEDULED, MessageStatus::IN_PROGRESS,
                            ], true)) {
                            if (null === $messageTemplate->getStartDate()) {
                                $messageTemplate->setStartDate(new \DateTime('now'));
                            }
                            if (MessageType::PUSH_NOTIFICATION === $messageTemplate->getType()->getValue()) {
                                $messageTemplate->setStatus(new MessageStatus(MessageStatus::PROCESSED));
                            }

                            foreach ($messageTemplate->getRecipients() as $recipientList) {
                                if (MessageType::PUSH_NOTIFICATION === $messageTemplate->getType()->getValue()) {
                                    if (!($this->sendExpoPushNotification($recipientList))) {
                                        ++$errorCount;

                                        $recipientList->getMessage()->setStatus(new MessageStatus(MessageStatus::FAILED));

                                        $this->logger->error('Unable to send notification to '.$recipientList->getCustomer()->getAccountNumber());
                                    } else {
                                        $recipientList->getMessage()->setDateSent(new \DateTime('now'));
                                        $recipientList->getMessage()->setStatus(new MessageStatus(MessageStatus::SENT));
                                    }

                                    $this->entityManager->persist($recipientList);
                                }
                                // Add for other types here.
                            }

                            $this->entityManager->persist($messageTemplate);
                            $this->entityManager->flush();

                            if ($errorCount > 0) {
                                $this->logger->info("Unable to send Notification to $errorCount Customers");
                            }
                        }
                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::MESSAGE_EXECUTE_SCHEDULE:
                    try {
                        $messageTemplate = $this->iriConverter->getItemFromIri($task['data']['messageTemplate']);

                        if (!($messageTemplate instanceof MessageTemplate)) {
                            throw new \InvalidArgumentException('Wrong Entity queued, Wrong queue perhaps?');
                        }

                        $messageTemplateScheduleJob = new DisqueJob([
                            'data' => [
                                'messageTemplate' => $this->iriConverter->getIriFromItem($messageTemplate),
                            ],
                            'type' => JobType::MESSAGE_EXECUTE,
                        ]);

                        if ($messageTemplate->getPlannedStartDate() <= new \DateTime()) {
                            $this->messageQueue->push($messageTemplateScheduleJob);
                            $action = 'Pushed';
                            $messageTemplate->setStatus(new MessageStatus(MessageStatus::IN_PROGRESS));
                            $logDate = (new \DateTime())->format('r');
                        } else {
                            $this->messageQueue->schedule($messageTemplateScheduleJob, $messageTemplate->getPlannedStartDate());
                            $action = 'Scheduled';
                            $messageTemplate->setStatus(new MessageStatus(MessageStatus::SCHEDULED));
                            $logDate = $messageTemplate->getPlannedStartDate()->format('r');
                        }

                        $this->entityManager->persist($messageTemplate);

                        $io->text(\sprintf('%s %s for %s at %s', $action, JobType::MESSAGE_EXECUTE, $messageTemplate->getMessageNumber(), $logDate));
                        $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::MESSAGE_EXECUTE, $messageTemplate->getMessageNumber(), $logDate));

                        $endDate = $messageTemplate->getPlannedEndDate();

                        if (null !== $endDate) {
                            $messageIri = $this->iriConverter->getIriFromItem($messageTemplate);
                            $messageEndJob = new DisqueJob([
                                'data' => [
                                    'messageTemplate' => $messageIri,
                                    'endDate' => $endDate->format('c'),
                                ],
                                'type' => JobType::MESSAGE_END,
                            ]);

                            if ($endDate <= new \DateTime()) {
                                $this->messageQueue->push($messageEndJob);
                                $action = 'Pushed';
                                $logDate = (new \DateTime())->format('r');
                            } else {
                                $this->messageQueue->schedule($messageEndJob, $endDate);
                                $action = 'Scheduled';
                                $logDate = $endDate->format('r');
                            }

                            $io->text(\sprintf('%s %s for %s at %s', $action, JobType::MESSAGE_END, $messageIri, $logDate));
                            $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::MESSAGE_END, $messageIri, $logDate));
                        }

                        $this->entityManager->flush();

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::SCHEDULE_MESSAGE_JOBS:
                    try {
                        $jobDate = new \DateTime($task['data']['date'], $this->timezone);

                        // search and queue all messages for the day
                        $startTime = clone $jobDate;
                        $startTime->setTime(0, 0, 0);
                        $endTime = clone $startTime;
                        $endTime->setTime(23, 59, 59);

                        $startTime->setTimezone($utcTimezone);
                        $endTime->setTimezone($utcTimezone);

                        $qb = $this->entityManager->getRepository(MessageTemplate::class)->createQueryBuilder('messageTemplate');
                        $expr = $qb->expr();
                        $messageTemplates = $qb->where($expr->gte('messageTemplate.plannedStartDate', $expr->literal($startTime->format('c'))))
                            ->andWhere($expr->lte('messageTemplate.plannedStartDate', $expr->literal($endTime->format('c'))))
                            ->andWhere($expr->neq('messageTemplate.status', $expr->literal(MessageStatus::ENDED)))
                            ->andWhere($expr->neq('messageTemplate.status', $expr->literal(MessageStatus::CANCELED)))
                            ->getQuery()
                            ->getResult();

                        foreach ($messageTemplates as $messageTemplate) {
                            if (MessageStatus::PROCESSED === $messageTemplate->getStatus()->getValue() && MessageType::PUSH_NOTIFICATION === $messageTemplate->getType()->getValue()) {
                                continue;
                            }

                            $messageTemplateScheduleJob = new DisqueJob([
                                'data' => [
                                    'messageTemplate' => $this->iriConverter->getIriFromItem($messageTemplate),
                                ],
                                'type' => JobType::MESSAGE_EXECUTE,
                            ]);

                            if ($messageTemplate->getPlannedStartDate() <= new \DateTime()) {
                                $this->messageQueue->push($messageTemplateScheduleJob);
                                $action = 'Pushed';
                                $messageTemplate->setStatus(new MessageStatus(MessageStatus::IN_PROGRESS));
                                $logDate = (new \DateTime())->format('r');
                            } else {
                                $this->messageQueue->schedule($messageTemplateScheduleJob, $messageTemplate->getPlannedStartDate());
                                $action = 'Scheduled';
                                $messageTemplate->setStatus(new MessageStatus(MessageStatus::SCHEDULED));
                                $logDate = $messageTemplate->getPlannedStartDate()->format('r');
                            }

                            $this->entityManager->persist($messageTemplate);

                            $io->text(\sprintf('%s %s for %s at %s', $action, JobType::MESSAGE_EXECUTE, $messageTemplate->getMessageNumber(), $logDate));
                            $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::MESSAGE_EXECUTE, $messageTemplate->getMessageNumber(), $logDate));
                        }
                        // search and queue all messages for the day

                        // messages ending today
                        $messageTemplateQb = $this->entityManager->getRepository(MessageTemplate::class)->createQueryBuilder('messageTemplate');
                        $expr = $messageTemplateQb->expr();

                        $messageTemplatesEndingToday = $messageTemplateQb->where($expr->gte('messageTemplate.plannedEndDate', $expr->literal($startTime->format('c'))))
                            ->andWhere($expr->lte('messageTemplate.plannedEndDate', $expr->literal($endTime->format('c'))))
                            ->andWhere($expr->neq('messageTemplate.status', $expr->literal(MessageStatus::ENDED)))
                            ->getQuery()
                            ->getResult();

                        foreach ($messageTemplatesEndingToday as $message) {
                            $endDate = $message->getPlannedEndDate();

                            if (null !== $endDate) {
                                $messageIri = $this->iriConverter->getIriFromItem($message);
                                $messageEndJob = new DisqueJob([
                                    'data' => [
                                        'messageTemplate' => $messageIri,
                                        'endDate' => $endDate->format('c'),
                                    ],
                                    'type' => JobType::MESSAGE_END,
                                ]);

                                if ($endDate <= new \DateTime()) {
                                    $this->messageQueue->push($messageEndJob);
                                    $action = 'Pushed';
                                    $logDate = (new \DateTime())->format('r');
                                } else {
                                    $this->messageQueue->schedule($messageEndJob, $endDate);
                                    $action = 'Scheduled';
                                    $logDate = $message->getPlannedEndDate()->format('r');
                                }

                                $io->text(\sprintf('%s %s for %s at %s', $action, JobType::MESSAGE_END, $messageIri, $logDate));
                                $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::MESSAGE_END, $messageIri, $logDate));
                            }
                        }

                        $this->entityManager->flush();

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::MESSAGE_END:
                    try {
                        //run the function
                        $messageTemplate = null;
                        $endDate = null;

                        try {
                            /**
                             * @var MessageTemplate
                             */
                            $messageTemplate = $this->iriConverter->getItemFromIri($task['data']['messageTemplate']);
                            $endDate = $task['data']['endDate'];
                        } catch (\Exception $e) {
                            $io->error($e->getMessage());
                            $this->logger->error($e->getMessage());
                        }

                        if (null !== $messageTemplate && $messageTemplate instanceof MessageTemplate && null !== $endDate && null !== $messageTemplate->getPlannedEndDate()) {
                            if ($endDate === $messageTemplate->getPlannedEndDate()->format('c') && MessageStatus::ENDED !== $messageTemplate->getStatus()->getValue()) {
                                $messageTemplate->setStatus(new MessageStatus(MessageStatus::ENDED));
                                $messageTemplate->setEndDate(new \DateTime('now'));

                                $this->entityManager->persist($messageTemplate);
                                $this->entityManager->flush();
                            }
                        }

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::MOTHER_OF_MESSAGE:
                    //run the function
                    $jobDate = new \DateTime($task['data']['date'], $this->timezone);

                    $queueFutureJobsOnly = false;

                    if (isset($task['data']['only-future-jobs'])) {
                        $queueFutureJobsOnly = (bool) $task['data']['only-future-jobs'];
                    }

                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:cron:queue-cron-job-schedule --queue=messages --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:cron:queue-cron-job-schedule --queue=messages --env=worker';
                        }

                        if (true === $queueFutureJobsOnly) {
                            $command .= ' --only-future-jobs';
                        }

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(300);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            throw new \Exception($process->getErrorOutput());
                        }

                        $io->text($process->getOutput());
                        $this->logger->info($process->getOutput());

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }

                    // spawn next day cron job
                    $nextDate = new \DateTime($task['data']['date'], $this->timezone);
                    $nextDate->modify('+1 day');
                    $nextDate->setTime(0, 0, 0);

                    $nextDayJob = new DisqueJob([
                        'data' => [
                            'date' => $nextDate->format('Y-m-d H:i:s'),
                        ],
                        'type' => JobType::MOTHER_OF_MESSAGE,
                    ]);

                    //convert to utc for checking
                    $nextDate->setTimezone($utcTimezone);
                    if ($nextDate <= new \DateTime()) {
                        $this->messageQueue->push($nextDayJob);
                        $action = 'Pushed';
                        $logDate = (new \DateTime())->format('r');
                    } else {
                        $jobTTL = 24.5 * 60 * 60;
                        $this->messageQueue->schedule($nextDayJob, $nextDate, ['ttl' => (int) $jobTTL]);
                        $action = 'Scheduled';
                        $logDate = $nextDate->format('r');
                    }
                    $io->text(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_MESSAGE, $logDate));
                    $this->logger->info(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_MESSAGE, $logDate));
                    // spawn next day cron job

                    $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
                default:
                    $error = true;
                    $errorMessageLog = \sprintf('[%s] Wrong Queue? Fail JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
            }

            if (true === $error) {
                // nack the job
                $this->messageQueue->failed($job);
                $io->error($errorMessageLog);
                $this->logger->error($errorMessageLog);
            } else {
                // ack the job
                $this->messageQueue->processed($job);
                $io->text($endMessageLog);
                $this->logger->info($endMessageLog);
            }

            $this->entityManager->clear();
            $io->newLine();
        }

        return 0;
    }

    private function sendToBot(array $data)
    {
        try {
            $job = $data['job'];
            $task = $data['task'];
            $text = \json_encode($task);

            if (isset($data['url'])) {
                $text = $text.', file url: '.$data['url'];
            }

            $attachment = [
                ['text' => $text],
            ];

            if (!empty($this->emergencyWebhookUrl)) {
                $client = new GuzzleClient();

                $headers = [
                    'User-Agent' => 'U-Centric API',
                    'Content-Type' => 'application/json',
                ];

                $payload = [
                    'text' => 'Failed Disque Job #'.$job->getId(),
                    'attachments' => $attachment,
                ];

                $submitRequest = new GuzzlePsr7Request('POST', $this->emergencyWebhookUrl, $headers, \json_encode($payload));
                $client->send($submitRequest);
            }
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
        }
    }

    private function sendExpoPushNotification(MessageRecipientListItem $recipient)
    {
        try {
            $tokens = [];

            foreach ($recipient->getMessageAddress() as $token) {
                if (1 === \preg_match('/^ExponentPushToken\[.*\]$/', $token)) {
                    $token = \preg_replace('/^ExponentPushToken\[/', '', $token);
                    $token = \rtrim($token, ']');
                    $tokens[] = $token;
                }
            }

            $tokens = \array_unique($tokens);
            $tokens = \array_values($tokens);
            $totalNotifications = \count($tokens);

            if ($totalNotifications > 0) {
                foreach ($tokens as $token) {
                    $this->notificationManager->sendNotification($recipient->getMessage()->getMessageTemplate()->getBody(),
                        $token, $recipient->getMessage()->getMessageTemplate()->getTitle());
                }
            }

            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
