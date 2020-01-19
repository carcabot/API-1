<?php

declare(strict_types=1);

namespace App\Command;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Campaign;
use App\Entity\CustomerAccount;
use App\Entity\DirectMailCampaignSourceListItem;
use App\Entity\EmailCampaignSchedule;
use App\Entity\EmailCampaignSourceList;
use App\Entity\EmailCampaignSourceListItem;
use App\Entity\Lead;
use App\Entity\MailgunBounceEvent;
use App\Entity\RecurringEmailCampaign;
use App\Entity\SourceList;
use App\Entity\UnsubscribeListItem;
use App\Enum\CampaignSourceType;
use App\Enum\CampaignStatus;
use App\Model\DirectMailCampaignFileGenerator;
use App\Repository\UnsubscribeListItemRepository;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use Psr\Log\LoggerInterface;
use Solvecrew\ExpoNotificationsBundle\Manager\NotificationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CampaignWorkerStartCommand extends Command
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
     * @var DirectMailCampaignFileGenerator
     */
    private $directMailCampaignFileGenerator;

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
    private $campaignQueue;

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
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var UnsubscribeListItem[]
     */
    private $exclusionList;

    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * @param EntityManagerInterface          $entityManager
     * @param DirectMailCampaignFileGenerator $directMailCampaignFileGenerator
     * @param IriConverterInterface           $iriConverter
     * @param LoggerInterface                 $logger
     * @param DisqueQueue                     $campaignQueue
     * @param DisqueQueue                     $mailerQueue
     * @param string                          $timezone
     * @param string                          $emergencyWebhookUrl
     * @param string                          $memoryLimit
     * @param ValidatorInterface              $validator
     * @param NotificationManager             $notificationManager
     */
    public function __construct(EntityManagerInterface $entityManager, DirectMailCampaignFileGenerator $directMailCampaignFileGenerator, IriConverterInterface $iriConverter, LoggerInterface $logger, DisqueQueue $campaignQueue, DisqueQueue $mailerQueue, string $timezone, string $emergencyWebhookUrl, string $memoryLimit, ValidatorInterface $validator, NotificationManager $notificationManager)
    {
        parent::__construct();

        $this->emergencyWebhookUrl = $emergencyWebhookUrl;
        $this->entityManager = $entityManager;
        $this->directMailCampaignFileGenerator = $directMailCampaignFileGenerator;
        $this->iriConverter = $iriConverter;
        $this->logger = $logger;
        $this->campaignQueue = $campaignQueue;
        $this->mailerQueue = $mailerQueue;
        $this->timezone = new \DateTimeZone($timezone);
        $this->memoryLimit = $memoryLimit;
        $this->validator = $validator;
        $this->notificationManager = $notificationManager;

        if ($this->entityManager->getRepository(UnsubscribeListItem::class) instanceof UnsubscribeListItemRepository) {
            $this->exclusionList = $this->entityManager->getRepository(UnsubscribeListItem::class)->findAllEmails();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:campaign-worker:start')
            ->setDescription('Starts the campaign worker.')
            ->setHelp(<<<'EOF'
The %command.name% command starts the campaign worker.
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

        $io->success('Worker ready to accept tasks in the campaigns queue.');
        $io->comment('Stop the worker with CONTROL-C.');

        while ($job = $this->campaignQueue->pull()) {
            if (!$job instanceof DisqueJob) {
                throw new \UnexpectedValueException();
            }
            $task = $job->getBody();

            if ($job->getNacks() > 0 || $job->getAdditionalDeliveries() > 0) {
                $this->campaignQueue->processed($job);

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
            $queueFutureJobsOnly = false;

            //log start of job
            $io->text(\sprintf('[%s] Running JOB %s.', (new \DateTime())->format('r'), $job->getId()));
            $io->text(\json_encode($task, JSON_PRETTY_PRINT));
            $this->logger->info('Running JOB '.\json_encode($task, JSON_PRETTY_PRINT));

            switch ($task['type']) {
                case JobType::CAMPAIGN_EXECUTE:
                    try {
                        //run the function
                        $campaign = null;
                        $filePaths = [];
                        $zipFile = null;

                        // error stuff
                        $emailConstraint = new Assert\Email();
                        $errorCount = 0;

                        try {
                            $campaign = $this->iriConverter->getItemFromIri($task['data']['campaign']);
                        } catch (\Exception $e) {
                            $io->error($e->getMessage());
                            $this->logger->error($e->getMessage());
                        }

                        if (null !== $campaign && $campaign instanceof Campaign) {
                            foreach ($campaign->getRecipientLists() as $recipientList) {
                                foreach ($recipientList->getItemListElement() as $recipientListItem) {
                                    if ($recipientListItem instanceof EmailCampaignSourceListItem) {
                                        if (\in_array($recipientListItem->getEmailAddress(), $this->exclusionList, true)) {
                                            continue;
                                        }
                                        $errors = $this->validator->validate(
                                            $recipientListItem->getEmailAddress(),
                                            $emailConstraint
                                        );

                                        if (0 === \count($errors)) {
                                            $this->mailerQueue->push(new DisqueJob([
                                                'data' => [
                                                    'campaign' => $task['data']['campaign'],
                                                    'emailCampaignSourceListItem' => $this->iriConverter->getIriFromItem($recipientListItem),
                                                ],
                                                'type' => JobType::CAMPAIGN_EXECUTE,
                                            ]));
                                        } else {
                                            ++$errorCount;
                                            $this->createBounceEvent($campaign, $recipientListItem);
                                        }
                                    } elseif ($recipientListItem instanceof DirectMailCampaignSourceListItem) {
                                        $filePaths[] = $this->directMailCampaignFileGenerator->generatePdf($campaign, $recipientListItem);
                                    }
                                }
                            }

                            $internalDocumentZipFile = $this->directMailCampaignFileGenerator->generateInternalDocumentZip($filePaths, $campaign);
                            if (null !== $internalDocumentZipFile) {
                                $campaign->setFile($internalDocumentZipFile);

                                $this->entityManager->persist($campaign);
                                $this->entityManager->flush();
                            }

                            if ($errorCount > 0) {
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
                case JobType::CAMPAIGN_EXECUTE_SCHEDULE:
                    try {
                        //run the function
                        $campaign = null;
                        $emailCampaignSchedule = null;

                        // error stuff
                        $emailConstraint = new Assert\Email();
                        $errorCount = 0;

                        try {
                            $campaign = $this->iriConverter->getItemFromIri($task['data']['campaign']);
                            $emailCampaignSchedule = $this->iriConverter->getItemFromIri($task['data']['emailCampaignSchedule']);
                        } catch (\Exception $e) {
                            $io->error($e->getMessage());
                            $this->logger->error($e->getMessage());
                        }

                        if (null !== $campaign && $campaign instanceof Campaign && null !== $emailCampaignSchedule && $emailCampaignSchedule instanceof EmailCampaignSchedule) {
                            $qb = $this->entityManager->getRepository(EmailCampaignSourceListItem::class)->createQueryBuilder('emailCampaignSourceListItem');
                            $subQuery = $this->entityManager->getRepository(Campaign::class)->createQueryBuilder('campaign');
                            $expr = $subQuery->expr();

                            $subQuery->select('listItem.id')
                                ->join('campaign.recipientLists', 'sourceList')
                                ->join('sourceList.itemListElement', 'listItem')
                                ->where($expr->eq('campaign', ':campaign'))
                                ->andWhere($expr->gte('listItem.position', ':startPosition'))
                                ->andWhere($expr->lte('listItem.position', ':endPosition'));

                            $recipientListItems = $qb->where($qb->expr()->in('emailCampaignSourceListItem.id', $subQuery->getDQL()))
                                ->setParameter('campaign', $emailCampaignSchedule->getCampaign())
                                ->setParameter('startPosition', $emailCampaignSchedule->getFromPosition())
                                ->setParameter('endPosition', $emailCampaignSchedule->getToPosition())
                                ->getQuery()
                                ->getResult();

                            foreach ($recipientListItems as $recipientListItem) {
                                $errors = $this->validator->validate(
                                    $recipientListItem->getEmailAddress(),
                                    $emailConstraint
                                );

                                if (0 === \count($errors)) {
                                    $this->mailerQueue->push(new DisqueJob([
                                        'data' => [
                                            'campaign' => $task['data']['campaign'],
                                            'emailCampaignSourceListItem' => $this->iriConverter->getIriFromItem($recipientListItem),
                                        ],
                                        'type' => JobType::CAMPAIGN_EXECUTE,
                                    ]));
                                } else {
                                    ++$errorCount;
                                    $this->createBounceEvent($campaign, $recipientListItem);
                                }
                            }

                            if ($errorCount > 0) {
                                $this->entityManager->flush();
                            }

                            $scheduleMaxDateQb = $this->entityManager->getRepository(EmailCampaignSchedule::class)->createQueryBuilder('schedule');
                            $expr = $scheduleMaxDateQb->expr();

                            $maxScheduleDate = $scheduleMaxDateQb->select('MAX(schedule.date)')
                                ->where($expr->eq('schedule.campaign', $expr->literal($campaign->getId())))
                                ->getQuery()
                                ->getSingleScalarResult();

                            if (null !== $maxScheduleDate) {
                                $lastScheduleDate = new \DateTime($maxScheduleDate);

                                if ($lastScheduleDate <= new \DateTime()) {
                                    $campaign->setStatus(new CampaignStatus(CampaignStatus::EXECUTED));
                                    $this->entityManager->persist($campaign);
                                    $this->entityManager->flush();
                                }
                            }
                        } else {
                            $io->text('Campaign not found.');
                            $this->logger->info('Campaign not found.');
                        }

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::SCHEDULE_CAMPAIGN_JOBS:
                    try {
                        $jobDate = new \DateTime($task['data']['date'], $this->timezone);

                        // search and queue all campaign schedules for the day
                        $startTime = clone $jobDate;
                        $startTime->setTime(0, 0, 0);
                        $endTime = clone $startTime;
                        $endTime->setTime(23, 59, 59);

                        $startTime->setTimezone($utcTimezone);
                        $endTime->setTimezone($utcTimezone);

                        $qb = $this->entityManager->getRepository(EmailCampaignSchedule::class)->createQueryBuilder('emailCampaignSchedule');
                        $expr = $qb->expr();
                        $emailCampaignSchedules = $qb->leftJoin('emailCampaignSchedule.campaign', 'campaign')
                            ->where($expr->gte('emailCampaignSchedule.date', $expr->literal($startTime->format('c'))))
                            ->andWhere($expr->lte('emailCampaignSchedule.date', $expr->literal($endTime->format('c'))))
                            ->andWhere($expr->eq('campaign.status', $expr->literal(CampaignStatus::SCHEDULED)))
                            ->getQuery()
                            ->getResult();

                        foreach ($emailCampaignSchedules as $emailCampaignSchedule) {
                            $emailCampaignScheduleIri = $this->iriConverter->getIriFromItem($emailCampaignSchedule);
                            $emailCampaignScheduleJob = new DisqueJob([
                                'data' => [
                                    'campaign' => $this->iriConverter->getIriFromItem($emailCampaignSchedule->getCampaign()),
                                    'emailCampaignSchedule' => $emailCampaignScheduleIri,
                                ],
                                'type' => JobType::CAMPAIGN_EXECUTE_SCHEDULE,
                            ]);

                            if ($emailCampaignSchedule->getDate() <= new \DateTime()) {
                                $this->campaignQueue->push($emailCampaignScheduleJob);
                                $action = 'Pushed';
                                $logDate = (new \DateTime())->format('r');
                            } else {
                                $this->campaignQueue->schedule($emailCampaignScheduleJob, $emailCampaignSchedule->getDate());
                                $action = 'Scheduled';
                                $logDate = $emailCampaignSchedule->getDate()->format('r');
                            }

                            $io->text(\sprintf('%s %s for %s at %s', $action, JobType::CAMPAIGN_EXECUTE_SCHEDULE, $emailCampaignScheduleIri, $logDate));
                            $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::CAMPAIGN_EXECUTE_SCHEDULE, $emailCampaignScheduleIri, $logDate));
                        }
                        // search and queue all campaign schedules for the day

                        // campaigns ending today
                        $campaignQb = $this->entityManager->getRepository(Campaign::class)->createQueryBuilder('campaign');
                        $expr = $campaignQb->expr();

                        $campaigns = $campaignQb->where($expr->gte('campaign.endDate', $expr->literal($startTime->format('c'))))
                            ->andWhere($expr->lte('campaign.endDate', $expr->literal($endTime->format('c'))))
                            ->andWhere($expr->neq('campaign.status', $expr->literal(CampaignStatus::ENDED)))
                            ->getQuery()
                            ->getResult();

                        foreach ($campaigns as $campaign) {
                            if (null !== $campaign->getEndDate()) {
                                $campaignIri = $this->iriConverter->getIriFromItem($campaign);
                                $campaignEndJob = new DisqueJob([
                                    'data' => [
                                        'campaign' => $campaignIri,
                                        'endDate' => $campaign->getEndDate()->format('c'),
                                    ],
                                    'type' => JobType::CAMPAIGN_END,
                                ]);

                                if ($campaign->getEndDate() <= new \DateTime()) {
                                    $this->campaignQueue->push($campaignEndJob);
                                    $action = 'Pushed';
                                    $logDate = (new \DateTime())->format('r');
                                } else {
                                    $this->campaignQueue->schedule($campaignEndJob, $campaign->getEndDate());
                                    $action = 'Scheduled';
                                    $logDate = $campaign->getEndDate()->format('r');
                                }

                                $io->text(\sprintf('%s %s for %s at %s', $action, JobType::CAMPAIGN_END, $campaignIri, $logDate));
                                $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::CAMPAIGN_END, $campaignIri, $logDate));
                            }
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::SCHEDULE_RECURRING_CAMPAIGN_JOBS:
                    try {
                        $jobDate = new \DateTime('now', $this->timezone);

                        // search and queue all campaign for the day
                        $startTime = clone $jobDate;
                        $startTime->setTime(0, 0, 0);
                        $endTime = clone $startTime;
                        $endTime->setTime(23, 59, 59);

                        $startTime->setTimezone($utcTimezone);
                        $endTime->setTimezone($utcTimezone);

                        $qb = $this->entityManager->getRepository(RecurringEmailCampaign::class)->createQueryBuilder('recurringCampaign');
                        $expr = $qb->expr();
                        $recurringEmailCampaigns = $qb->leftJoin('recurringCampaign.campaign', 'campaign')
                            ->andWhere($expr->lte('campaign.startDate', $expr->literal($endTime->format('c'))))
                            ->andWhere($expr->eq('campaign.status', $expr->literal(CampaignStatus::SCHEDULED)))
                            ->getQuery()
                            ->getResult();

                        foreach ($recurringEmailCampaigns as $recurringEmailCampaign) {
                            $recurringEmailCampaignIri = $this->iriConverter->getIriFromItem($recurringEmailCampaign);
                            $currentCampaign = $recurringEmailCampaign->getCampaign();

                            $expressionLogic = $recurringEmailCampaign->getSourceListGeneratorExpression();
                            $logicParts = \explode('|', $expressionLogic);
                            $propertyPaths = \explode('.', $logicParts[0]);

                            $customerQb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');

                            if (\count($propertyPaths) > 1) {
                                for ($idx = 0; $idx < \count($propertyPaths) - 1; ++$idx) {
                                    $alias = "$propertyPaths[idx]Alias";
                                    $customerQb->leftJoin("customer.$propertyPaths[idx]", $alias);
                                }

                                $customerQb = $this->mapConditionToExpression('customer', $logicParts[1], $customerQb, $propertyPaths[\count($propertyPaths) - 1], $logicParts[2]);
                            } else {
                                $customerQb = $this->mapConditionToExpression('customer', $logicParts[1], $customerQb, $propertyPaths[0], $logicParts[2]);
                            }

                            $customers = $customerQb->getQuery()->getResult();

                            foreach ($customers as $customer) {
                                $sourceList = $this->createSourceList(new EmailCampaignSourceList(), $customer);
                                $currentCampaign->addRecipientList($sourceList);
                            }

                            $this->entityManager->persist($currentCampaign);
                            $this->entityManager->flush();

                            $recurringEmailCampaignJob = new DisqueJob([
                                'data' => [
                                    'campaign' => $this->iriConverter->getIriFromItem($recurringEmailCampaign->getCampaign()),
                                ],
                                'type' => JobType::CAMPAIGN_EXECUTE,
                            ]);

                            $this->campaignQueue->push($recurringEmailCampaignJob);
                            $action = 'Pushed';
                            $logDate = (new \DateTime())->format('r');

                            $io->text(\sprintf('%s %s for %s at %s', $action, JobType::SCHEDULE_RECURRING_CAMPAIGN_JOBS, $recurringEmailCampaignIri, $logDate));
                            $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::SCHEDULE_RECURRING_CAMPAIGN_JOBS, $recurringEmailCampaignIri, $logDate));
                        }
                        // search and queue all campaign for the day

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::MOTHER_OF_CAMPAIGN:
                    //run the function
                    $jobDate = new \DateTime($task['data']['date'], $this->timezone);

                    if (isset($task['data']['only-future-jobs'])) {
                        $queueFutureJobsOnly = (bool) $task['data']['only-future-jobs'];
                    }

                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:cron:queue-cron-job-schedule --queue=campaigns --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:cron:queue-cron-job-schedule --queue=campaigns --env=worker';
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
                        'type' => JobType::MOTHER_OF_CAMPAIGN,
                    ]);

                    //convert to utc for checking
                    $nextDate->setTimezone($utcTimezone);
                    if ($nextDate <= new \DateTime()) {
                        $this->campaignQueue->push($nextDayJob);
                        $action = 'Pushed';
                        $logDate = (new \DateTime())->format('r');
                    } else {
                        $jobTTL = 24.5 * 60 * 60;
                        $this->campaignQueue->schedule($nextDayJob, $nextDate, ['ttl' => (int) $jobTTL]);
                        $action = 'Scheduled';
                        $logDate = $nextDate->format('r');
                    }
                    $io->text(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_CAMPAIGN, $logDate));
                    $this->logger->info(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_CAMPAIGN, $logDate));
                    // spawn next day cron job

                    $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
                case JobType::CAMPAIGN_END:
                    try {
                        //run the function
                        $campaign = null;
                        $endDate = null;

                        try {
                            $campaign = $this->iriConverter->getItemFromIri($task['data']['campaign']);
                            $endDate = $task['data']['endDate'];
                        } catch (\Exception $e) {
                            $io->error($e->getMessage());
                            $this->logger->error($e->getMessage());
                        }

                        if (null !== $campaign && $campaign instanceof Campaign && null !== $endDate && null !== $campaign->getEndDate()) {
                            if ($endDate === $campaign->getEndDate()->format('c') && CampaignStatus::ENDED !== $campaign->getStatus()->getValue()) {
                                $campaign->setStatus(new CampaignStatus(CampaignStatus::ENDED));

                                $this->entityManager->persist($campaign);
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
                default:
                    $error = true;
                    $errorMessageLog = \sprintf('[%s] Wrong Queue? Fail JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
            }

            if (true === $error) {
                // nack the job
                $this->campaignQueue->failed($job);
                $io->error($errorMessageLog);
                $this->logger->error($errorMessageLog);
            } else {
                // ack the job
                $this->campaignQueue->processed($job);
                $io->text($endMessageLog);
                $this->logger->info($endMessageLog);
            }

            $this->entityManager->clear();
            $io->newLine();
        }

        return 0;
    }

    private function mapConditionToExpression(string $baseEntity, string $condition, QueryBuilder $qb, string $property, $value): QueryBuilder
    {
        $expr = $qb->expr();
        switch ($condition) {
            case 'gt':
                return $qb->andWhere($expr->gt("$baseEntity.$property", $value));
            case 'gte':
                return $qb->andWhere($expr->gte("$baseEntity.$property", $value));
            case 'lt':
                return $qb->andWhere($expr->lt("$baseEntity.$property", $value));
            case 'lte':
                return $qb->andWhere($expr->lte("$baseEntity.$property", $value));
            case 'eq':
                return $qb->andWhere($expr->eq("$baseEntity.$property", $value));
            default:
                return $qb;
        }
    }

    private function createSourceList(SourceList $sourceList, $entity, string $class = CustomerAccount::class)
    {
        $contactPointsBase = null;
        if (null !== $entity->getPersonDetails()) {
            $contactPointsBase = $entity->getPersonDetails();
        } elseif (null !== $entity->getCorporationDetails()) {
            $contactPointsBase = $entity->getCorporationDetails();
        }

        if (null !== $contactPointsBase) {
            foreach ($contactPointsBase->getContactPoints() as $contactPoint) {
                if (\count($contactPoint->getEmails()) > 0) {
                    $email = \array_values(\array_slice($contactPoint->getEmails(), -1))[0];

                    $listItem = new EmailCampaignSourceListItem();
                    $listItem->setEmailAddress($email);

                    if (CustomerAccount::class === $class) {
                        $listItem->setSource(new CampaignSourceType(CampaignSourceType::CUSTOMER_ACCOUNT));
                        $listItem->setCustomer($entity);
                    } elseif (Lead::class === $class) {
                        $listItem->setSource(new CampaignSourceType(CampaignSourceType::LEAD));
                        $listItem->setLead($entity);
                    }

                    $listItem->setValue($email);
                    $this->entityManager->persist($listItem);

                    $sourceList->addItemListElement($listItem);
                }
            }

            return $sourceList;
        }

        throw new \Exception('Contact Points not found');
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

    private function createBounceEvent(Campaign $campaign, EmailCampaignSourceListItem $recipient)
    {
        $now = new \DateTime();
        $timestamp = $now->getTimestamp();

        $bounceEvent = new MailgunBounceEvent();
        $bounceEvent->setCampaign($campaign);
        $bounceEvent->setRecipient($recipient);
        $bounceEvent->setMailgunEventId(\uniqid());
        $bounceEvent->setDateBounced($timestamp);

        $this->entityManager->persist($bounceEvent);
    }
}
