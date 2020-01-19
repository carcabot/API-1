<?php

declare(strict_types=1);

namespace App\Command;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\ApplicationRequest;
use App\Entity\ContactPoint;
use App\Entity\EmailActivity;
use App\Entity\Quotation;
use App\Enum\ApplicationRequestStatus;
use App\Enum\EmailType;
use App\Model\QuotationFileGenerator;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ApplicationRequestWorkerStartCommand extends Command
{
    /**
     * @var string
     */
    private $emergencyWebhookUrl;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
    private $applicationRequestQueue;

    /**
     * @var DisqueQueue
     */
    private $mailerQueue;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var string
     */
    private $memoryLimit;

    /**
     * @var QuotationFileGenerator
     */
    private $quotationFileGenerator;

    /**
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param LoggerInterface        $logger
     * @param DisqueQueue            $applicationRequestQueue
     * @param DisqueQueue            $mailerQueue
     * @param string                 $timezone
     * @param string                 $emergencyWebhookUrl
     * @param string                 $memoryLimit
     * @param QuotationFileGenerator $quotationFileGenerator
     */
    public function __construct(EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, LoggerInterface $logger, DisqueQueue $applicationRequestQueue, DisqueQueue $mailerQueue, string $timezone, string $emergencyWebhookUrl, string $memoryLimit, QuotationFileGenerator $quotationFileGenerator)
    {
        parent::__construct();

        $this->emergencyWebhookUrl = $emergencyWebhookUrl;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->logger = $logger;
        $this->applicationRequestQueue = $applicationRequestQueue;
        $this->mailerQueue = $mailerQueue;
        $this->memoryLimit = $memoryLimit;
        $this->timezone = new \DateTimeZone($timezone);
        $this->memoryLimit = $memoryLimit;
        $this->quotationFileGenerator = $quotationFileGenerator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:application-request-worker:start')
            ->setDescription('Starts the application request worker.')
            ->setHelp(<<<'EOF'
The %command.name% command starts the application request worker.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->success('Worker ready to accept tasks in the application request queue.');
        $io->comment('Stop the worker with CONTROL-C.');
        $utcTimezone = new \DateTimeZone('UTC');

        while ($job = $this->applicationRequestQueue->pull()) {
            if (!$job instanceof DisqueJob) {
                throw new \UnexpectedValueException();
            }
            $task = $job->getBody();

            if ($job->getNacks() > 0 || $job->getAdditionalDeliveries() > 0) {
                $this->applicationRequestQueue->processed($job);

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
            $queueFutureJobsOnly = false;

            //log start of job
            $io->text(\sprintf('[%s] Running JOB %s.', (new \DateTime())->format('r'), $job->getId()));
            $io->text(\json_encode($task, JSON_PRETTY_PRINT));
            $this->logger->info('Running JOB '.\json_encode($task, JSON_PRETTY_PRINT));

            switch ($task['type']) {
                case JobType::APPLICATION_REQUEST_NOTIFY_AUTHORIZATION_URL:
                    /**
                     * @var ApplicationRequest
                     */
                    $applicationRequest = null;

                    try {
                        /**
                         * @var ApplicationRequest
                         */
                        $applicationRequestId = $task['data']['id'];
                        $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['id' => $applicationRequestId]);

                        if (null !== $applicationRequest && ApplicationRequestStatus::PENDING === $applicationRequest->getStatus()->getValue()) {
                            $this->mailerQueue->push(new DisqueJob([
                                'data' => [
                                    'applicationRequest' => $this->iriConverter->getIriFromItem($applicationRequest),
                                ],
                                'type' => JobType::APPLICATION_REQUEST_NOTIFY_AUTHORIZATION_URL,
                            ]));

                            /**
                             * @var EmailActivity
                             */
                            $emailActivity = new EmailActivity();
                            if (null !== $applicationRequest->getPersonDetails()) {
                                /**
                                 * @var ContactPoint[]
                                 */
                                $contactPoints = $applicationRequest->getPersonDetails()->getContactPoints();
                                $isEmailSet = false;

                                foreach ($contactPoints as $contactPoint) {
                                    foreach ($contactPoint->getEmails() as $email) {
                                        $emailActivity->addToRecipient($email);
                                        $isEmailSet = true;
                                        break;
                                    }
                                    if ($isEmailSet) {
                                        break;
                                    }
                                }

                                $emailActivity->setType(new EmailType(EmailType::APPLICATION_REQUEST_AUTHORIZATION_REMINDER));
                                $this->entityManager->persist($emailActivity);

                                $applicationRequest->addActivity($emailActivity);

                                $this->entityManager->persist($applicationRequest);
                                $this->entityManager->flush();
                            }

                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                    } catch (\Exception $e) {
                        $error = true;
                        $errorMessageLog = $e->getMessage();
                    }
                    break;
                case JobType::APPLICATION_REQUEST_SCHEDULE_NOTIFY_AUTHORIZATION_URL:
                    try {
                        $startTime = new \DateTime($task['data']['date'], $this->timezone);
                        $startTime->add(new \DateInterval('P3D'));
                        $startTime->setTime(0, 0, 0);
                        $endTime = new \DateTime($task['data']['date'], $this->timezone);
                        $endTime->add(new \DateInterval('P3D'));
                        $endTime->setTime(23, 59, 59);

                        $startTime->setTimezone($utcTimezone);
                        $endTime->setTimezone($utcTimezone);

                        $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('appl');
                        $expr = $qb->expr();

                        $applicationRequests = $qb->select('appl')->leftJoin('appl.urlToken', 'toke')
                            ->where($expr->gte('toke.validThrough', $expr->literal($startTime->format('c'))))
                            ->andWhere($expr->lte('toke.validThrough', $expr->literal($endTime->format('c'))))
                            ->andWhere($expr->eq('appl.status', $expr->literal(ApplicationRequestStatus::PENDING)))
                            ->getQuery()->getResult();

                        if (\count($applicationRequests) > 0) {
                            foreach ($applicationRequests as $applicationRequest) {
                                $applicationRequestEmailReminderSchedule = new DisqueJob([
                                    'data' => [
                                        'id' => $applicationRequest->getId(),
                                    ],
                                    'type' => JobType::APPLICATION_REQUEST_NOTIFY_AUTHORIZATION_URL,
                                ]);

                                $scheduleTime = new \DateTime();
                                $hour = (int) $applicationRequest->getUrlToken()->getValidThrough()->format('h');
                                $min = (int) $applicationRequest->getUrlToken()->getValidThrough()->format('i');
                                $sec = (int) $applicationRequest->getUrlToken()->getValidThrough()->format('s');

                                $scheduleTime->setTime($hour, $min, $sec);

                                if ($scheduleTime <= new \DateTime()) {
                                    $this->applicationRequestQueue->push($applicationRequestEmailReminderSchedule);
                                    $action = 'Pushed';
                                    $logDate = (new \DateTime())->format('r');
                                } else {
                                    $this->applicationRequestQueue->schedule($applicationRequestEmailReminderSchedule, $scheduleTime);
                                    $action = 'Scheduled';
                                    $logDate = $scheduleTime->format('r');
                                }

                                $io->text(\sprintf('%s %s for %s at %s', $action, JobType::APPLICATION_REQUEST_NOTIFY_AUTHORIZATION_URL, $applicationRequest->getId(), $logDate));
                                $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::APPLICATION_REQUEST_NOTIFY_AUTHORIZATION_URL, $applicationRequest->getId(), $logDate));
                            }
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::APPLICATION_REQUEST_SCHEDULE_EXPIRE_AUTHORIZATION_URL:
                    try {
                        $startTime = new \DateTime($task['data']['date'], $this->timezone);
                        $startTime->setTime(0, 0, 0);

                        $endTime = new \DateTime($task['data']['date'], $this->timezone);
                        $endTime->setTime(23, 59, 59);

                        $startTime->setTimezone($utcTimezone);
                        $endTime->setTimezone($utcTimezone);

                        $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('appl');

                        $expr = $qb->expr();
                        $applicationRequests = $qb->select('appl')->leftJoin('appl.urlToken', 'tok')
                            ->where($expr->gte('tok.validThrough', $expr->literal($startTime->format('c'))))
                            ->andWhere($expr->lte('tok.validThrough', $expr->literal($endTime->format('c'))))
                            ->andWhere($expr->eq('appl.status', $expr->literal(ApplicationRequestStatus::PENDING)))
                            ->getQuery()->getResult();

                        if (\count($applicationRequests) > 0) {
                            foreach ($applicationRequests as $applicationRequest) {
                                $expireUrlJob = new DisqueJob([
                                    'data' => [
                                        'id' => $applicationRequest->getId(),
                                    ],
                                    'type' => JobType::APPLICATION_REQUEST_EXPIRE_AUTHORIZATION_URL,
                                ]);

                                if ($applicationRequest->getUrlToken()->getValidThrough() <= new \DateTime()) {
                                    $this->applicationRequestQueue->push($expireUrlJob);
                                    $action = 'Pushed';
                                    $logDate = (new \DateTime())->format('r');
                                } else {
                                    $this->applicationRequestQueue->schedule($expireUrlJob, $applicationRequest->getUrlToken()->getValidThrough());
                                    $action = 'Scheduled';
                                    $logDate = $applicationRequest->getUrlToken()->getValidThrough()->format('r');
                                }

                                $io->text(\sprintf('%s %s for %s at %s', $action, JobType::APPLICATION_REQUEST_EXPIRE_AUTHORIZATION_URL, $applicationRequest->getId(), $logDate));
                                $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::APPLICATION_REQUEST_EXPIRE_AUTHORIZATION_URL, $applicationRequest->getId(), $logDate));
                            }
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::APPLICATION_REQUEST_EXPIRE_AUTHORIZATION_URL:
                    /**
                     * @var ApplicationRequest
                     */
                    $applicationRequest = null;

                    try {
                        /**
                         * @var ApplicationRequest
                         */
                        $applicationRequestId = $task['data']['id'];
                        $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy(['id' => $applicationRequestId]);

                        if (null !== $applicationRequest && ApplicationRequestStatus::PENDING === $applicationRequest->getStatus()->getValue()) {
                            $applicationRequest->setStatus(new ApplicationRequestStatus(ApplicationRequestStatus::AUTHORIZATION_URL_EXPIRED));

                            $this->entityManager->persist($applicationRequest);
                            $this->entityManager->flush();

                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                    } catch (\Exception $e) {
                        $error = true;
                        $errorMessageLog = $e->getMessage();
                    }
                    break;
                case JobType::LEAD_CONVERT_STATUS:
                    //run the function

                    $customerAccountNumber = $task['data']['customerAccountNumber'];

                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:lead:convert --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:lead:convert --env=worker';
                        }

                        if (null !== $customerAccountNumber) {
                            $command .= " --customerId={$customerAccountNumber}";
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
                    break;
                case JobType::MOTHER_OF_APPLICATION_REQUEST:
                    //run the function
                    $jobDate = new \DateTime($task['data']['date'], $this->timezone);

                    if (isset($task['data']['only-future-jobs'])) {
                        $queueFutureJobsOnly = (bool) $task['data']['only-future-jobs'];
                    }

                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:cron:queue-cron-job-schedule --queue=application_requests --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:cron:queue-cron-job-schedule --queue=application_requests --env=worker';
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

                    //spawn next day cron job
                    $nextDate = new \DateTime($task['data']['date'], $this->timezone);
                    $nextDate->modify('+1 day');
                    $nextDate->setTime(0, 0, 0);

                    $nextDayJob = new DisqueJob([
                        'data' => [
                            'date' => $nextDate->format('Y-m-d H:i:s'),
                        ],
                        'type' => JobType::MOTHER_OF_APPLICATION_REQUEST,
                    ]);

                    //convert to utc for checking
                    $nextDate->setTimezone($utcTimezone);
                    if ($nextDate <= new \DateTime()) {
                        $this->applicationRequestQueue->push($nextDayJob);
                        $action = 'Pushed';
                        $logDate = (new \DateTime())->format('r');
                    } else {
                        $jobTTL = 24.5 * 60 * 60;
                        $this->applicationRequestQueue->schedule($nextDayJob, $nextDate, ['ttl' => (int) $jobTTL]);
                        $action = 'Scheduled';
                        $logDate = $nextDate->format('r');
                    }
                    $io->text(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_APPLICATION_REQUEST, $logDate));
                    $this->logger->info(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_APPLICATION_REQUEST, $logDate));
                    //spawn next day cron job

                    $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
                case JobType::APPLICATION_REQUEST_UPDATE_CACHE_TABLE:
                    /**
                     * @var ApplicationRequest
                     */
                    $applicationRequest = null;

                    try {
                        $applicationRequestId = $task['data']['id'];
                        $operationMode = $task['data']['mode'];

                        if ('' !== $this->memoryLimit) {
                            $command = "php -d memory_limit=$this->memoryLimit bin/console app:application-request:update-cache-table --id=$applicationRequestId --mode=$operationMode --env=worker";
                        } else {
                            $command = "php bin/console app:application-request:update-cache-table --id=$applicationRequestId --mode=$operationMode --env=worker";
                        }

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(7200);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $io->error($process->getErrorOutput());
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                    } catch (\Exception $e) {
                        $error = true;
                        $errorMessageLog = $e->getMessage();
                    }
                    break;
                case JobType::QUOTATION_UPDATE_FILE:
                    /**
                     * @var Quotation
                     */
                    $quotation = null;

                    try {
                        $quotationId = $task['data']['id'];
                        /**
                         * @var Quotation
                         */
                        $quotation = $this->iriConverter->getItemFromIri($quotationId);

                        $quotationAttachmentFilePath = $this->quotationFileGenerator->generatePdf($quotation);

                        $quotationAttachment = $this->quotationFileGenerator->convertFileToDigitalDocument($quotationAttachmentFilePath);

                        $quotation->setFile($quotationAttachment);

                        $this->entityManager->persist($quotation);
                        $this->entityManager->flush();

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $e) {
                        $error = true;
                        $errorMessageLog = $e->getMessage();
                    }
                    break;
                default:
                    $error = true;
                    $errorMessageLog = \sprintf('[%s] Wrong Queue? Fail JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
            }

            if (true === $error) {
                // nack the job
                $this->applicationRequestQueue->failed($job);
                $io->error($errorMessageLog);
                $this->logger->error($errorMessageLog);
            } else {
                // ack the job
                $this->applicationRequestQueue->processed($job);
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
}
