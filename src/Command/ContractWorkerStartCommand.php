<?php

declare(strict_types=1);

namespace App\Command;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Disque\JobType;
use App\Entity\Contract;
use App\Entity\CustomerAccount;
use App\Enum\AccountCategory;
use App\Enum\ContractStatus;
use App\Model\CustomerAccountPortalEnableUpdater;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Ds\Map;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ContractWorkerStartCommand extends Command
{
    /**
     * @var CustomerAccountPortalEnableUpdater
     */
    private $customerAccountPortalEnableUpdater;
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
    private $contractQueue;

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
    private $profile;

    /**
     * @var string
     */
    private $memoryLimit;

    /**
     * @param CustomerAccountPortalEnableUpdater $customerAccountPortalEnableUpdater
     * @param EntityManagerInterface             $entityManager
     * @param IriConverterInterface              $iriConverter
     * @param LoggerInterface                    $logger
     * @param DisqueQueue                        $contractQueue
     * @param DisqueQueue                        $mailerQueue
     * @param string                             $timezone
     * @param string                             $emergencyWebhookUrl
     * @param string                             $profile
     * @param string                             $memoryLimit
     */
    public function __construct(CustomerAccountPortalEnableUpdater $customerAccountPortalEnableUpdater, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, LoggerInterface $logger, DisqueQueue $contractQueue, DisqueQueue $mailerQueue, string $timezone, string $emergencyWebhookUrl, string $profile, string $memoryLimit)
    {
        parent::__construct();

        $this->customerAccountPortalEnableUpdater = $customerAccountPortalEnableUpdater;
        $this->emergencyWebhookUrl = $emergencyWebhookUrl;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->logger = $logger;
        $this->contractQueue = $contractQueue;
        $this->mailerQueue = $mailerQueue;
        $this->timezone = new \DateTimeZone($timezone);
        $this->profile = $profile;
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:contract-worker:start')
            ->setDescription('Starts the application request worker.')
            ->setHelp(<<<'EOF'
The %command.name% command starts the application request worker.
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->success('Worker ready to accept tasks in the contract queue.');
        $io->comment('Stop the worker with CONTROL-C.');
        $utcTimezone = new \DateTimeZone('UTC');

        while ($job = $this->contractQueue->pull()) {
            if (!$job instanceof DisqueJob) {
                throw new \UnexpectedValueException();
            }
            $task = $job->getBody();

            if ($job->getNacks() > 0 || $job->getAdditionalDeliveries() > 0) {
                $this->contractQueue->processed($job);

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
                case JobType::CONTRACT_END_NOTIFY_THREE_DAYS_NOTICE:
                case JobType::CONTRACT_END_NOTIFY_TEN_DAYS_NOTICE:
                    try {
                        $contractId = $task['data']['id'];
                        $contract = $this->entityManager->getRepository(Contract::class)->find((int) $contractId);

                        if (null !== $contract) {
                            $this->mailerQueue->push(new DisqueJob([
                                'data' => [
                                    'contract' => $this->iriConverter->getIriFromItem($contract),
                                ],
                                'type' => $task['type'],
                            ]));
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CONTRACT_END_NOTIFY:
                    try {
                        if ('unionpower' === $this->profile) {
                            $jobDate = new \DateTime($task['data']['date'], $this->timezone);
                            $nextDate = clone $jobDate;

                            $noticeDate = clone $nextDate;
                            $noticeDate->add(new \DateInterval('P10D'));

                            $startTime = clone $noticeDate;
                            $startTime->setTime(0, 0, 0);

                            $endTime = clone $startTime;
                            $endTime->setTime(23, 59, 59);

                            $startTime->setTimezone($utcTimezone);
                            $endTime->setTimezone($utcTimezone);

                            $qb = $this->entityManager->getRepository(Contract::class)->createQueryBuilder('contract');

                            $expr = $qb->expr();
                            $contracts = $qb->where($expr->gte('contract.lockInDate', $expr->literal($startTime->format('c'))))
                                ->andWhere($expr->lte('contract.lockInDate', $expr->literal($endTime->format('c'))))
                                ->getQuery()
                                ->getResult();

                            if (\count($contracts) > 0) {
                                foreach ($contracts as $contract) {
                                    $contractEmailReminderSchedule = new DisqueJob([
                                        'data' => [
                                            'id' => $contract->getId(),
                                        ],
                                        'type' => JobType::CONTRACT_END_NOTIFY_TEN_DAYS_NOTICE,
                                    ]);

                                    $scheduleTime = new \DateTime();
                                    $hour = (int) $contract->getLockInDate()->format('h');
                                    $min = (int) $contract->getLockInDate()->format('i');
                                    $sec = (int) $contract->getLockInDate()->format('s');

                                    $scheduleTime->setTime($hour, $min, $sec);

                                    if ($scheduleTime <= new \DateTime()) {
                                        $this->contractQueue->push($contractEmailReminderSchedule);
                                        $action = 'Pushed';
                                        $logDate = (new \DateTime())->format('r');
                                    } else {
                                        $this->contractQueue->schedule($contractEmailReminderSchedule, $scheduleTime);
                                        $action = 'Scheduled';
                                        $logDate = $scheduleTime->format('r');
                                    }

                                    $io->text(\sprintf('%s %s for %s at %s', $action, JobType::CONTRACT_END_NOTIFY_TEN_DAYS_NOTICE, $contract->getId(), $logDate));
                                    $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::CONTRACT_END_NOTIFY_TEN_DAYS_NOTICE, $contract->getId(), $logDate));
                                }
                            }
                            //schedule contract end notice 10 days

                            //schedule contract end notice 3 days
                            $noticeDate = clone $nextDate;
                            $noticeDate->add(new \DateInterval('P3D'));

                            $startTime = clone $noticeDate;
                            $startTime->setTime(0, 0, 0);
                            $endTime = clone $startTime;
                            $endTime->setTime(23, 59, 59);

                            $startTime->setTimezone($utcTimezone);
                            $endTime->setTimezone($utcTimezone);

                            $qb = $this->entityManager->getRepository(Contract::class)->createQueryBuilder('contract');

                            $expr = $qb->expr();
                            $contracts = $qb->where($expr->gte('contract.lockInDate', $expr->literal($startTime->format('c'))))
                                ->andWhere($expr->lte('contract.lockInDate', $expr->literal($endTime->format('c'))))
                                ->getQuery()
                                ->getResult();

                            if (\count($contracts) > 0) {
                                foreach ($contracts as $contract) {
                                    $contractEmailReminderSchedule = new DisqueJob([
                                        'data' => [
                                            'id' => $contracts->getId(),
                                        ],
                                        'type' => JobType::CONTRACT_END_NOTIFY_THREE_DAYS_NOTICE,
                                    ]);

                                    $scheduleTime = new \DateTime();
                                    $hour = (int) $contract->getLockInDate()->format('h');
                                    $min = (int) $contract->getLockInDate()->format('i');
                                    $sec = (int) $contract->getLockInDate()->format('s');

                                    $scheduleTime->setTime($hour, $min, $sec);

                                    if ($scheduleTime <= new \DateTime()) {
                                        $this->contractQueue->push($contractEmailReminderSchedule);
                                        $action = 'Pushed';
                                        $logDate = (new \DateTime())->format('r');
                                    } else {
                                        $this->contractQueue->schedule($contractEmailReminderSchedule, $scheduleTime);
                                        $action = 'Scheduled';
                                        $logDate = $scheduleTime->format('r');
                                    }

                                    $io->text(\sprintf('%s %s for %s at %s', $action, JobType::CONTRACT_END_NOTIFY_THREE_DAYS_NOTICE, $contract->getId(), $logDate));
                                    $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::CONTRACT_END_NOTIFY_THREE_DAYS_NOTICE, $contract->getId(), $logDate));
                                }
                            }
                            //schedule contract end notice 3 days

                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CONTRACT_UPDATE_CACHE_TABLE:
                    /**
                     * @var Contract
                     */
                    $contract = null;

                    try {
                        $contractId = $task['data']['id'];
                        $operationMode = $task['data']['mode'];

                        if ('' !== $this->memoryLimit) {
                            $command = "php -d memory_limit=$this->memoryLimit bin/console app:contract:update-cache-table --id=$contractId --mode=$operationMode --env=worker";
                        } else {
                            $command = "php bin/console app:contract:update-cache-table --id=$contractId --mode=$operationMode --env=worker";
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
                case JobType::CUSTOMER_PORTAL_DISABLE:
                    try {
                        $utcTimezone = new \DateTimeZone('UTC');
                        $qb = $this->entityManager->getRepository(Contract::class)->createQueryBuilder('contract');
                        $expr = $qb->expr();

                        $startTime = new \DateTime($task['data']['date'], $this->timezone);
                        $startTime->sub(new \DateInterval('P90D'));
                        $startTime->setTime(0, 0, 0);
                        $startTime->setTimezone($utcTimezone);

                        $endTime = new \DateTime($task['data']['date'], $this->timezone);
                        $endTime->sub(new \DateInterval('P90D'));
                        $endTime->setTime(23, 59, 59);
                        $endTime->setTimezone($utcTimezone);

                        $contracts = $qb->where($expr->andX(
                                    $expr->gte('contract.endDate', $expr->literal($startTime->format('c'))),
                                    $expr->lte('contract.endDate', $expr->literal($endTime->format('c')))))
                            ->getQuery()
                            ->getResult();

                        /**
                         * @var Map<string, int>
                         */
                        $checkedContracts = new Map();

                        foreach ($contracts as $contract) {
                            if ($checkedContracts->hasKey($contract->getContractNumber())) {
                                continue;
                            }

                            $checkedContracts->put($contract->getContractNumber(), 1);
                            $disablePortal = true;
                            $customerAccount = $contract->getCustomer();
                            $customerCategories = $customerAccount->getCategories();
                            $customerContracts = [];

                            foreach ($customerCategories as $category) {
                                if (AccountCategory::CONTACT_PERSON === $category) {
                                    $relationships = $customerAccount->getRelationships();

                                    foreach ($relationships as $relationship) {
                                        $customerContracts = \array_merge($customerContracts, $relationship->getContracts());
                                    }
                                } elseif (AccountCategory::CUSTOMER === $category) {
                                    $customerContracts = \array_merge($customerContracts, $customerAccount->getContracts());
                                }
                            }

                            foreach ($customerContracts as $customerContract) {
                                if (null === $customerContract->getContractNumber()) {
                                    continue;
                                }

                                if (null === $customerContract->getEndDate()) {
                                    $disablePortal = false;
                                    break;
                                }

                                if (!$checkedContracts->hasKey($customerContract->getContractNumber())) {
                                    $checkedContracts->put($customerContract->getContractNumber(), 1);
                                }

                                if (ContractStatus::ACTIVE === $customerContract->getStatus()->getValue() && $customerContract->getEndDate() > $endTime) {
                                    $disablePortal = false;
                                    break;
                                }
                            }

                            if ($disablePortal) {
                                $customerAccount->setCustomerPortalEnabled(false);
                                $this->entityManager->persist($customerAccount);
                            }
                        }

                        $this->entityManager->flush();

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CUSTOMER_PORTAL_ENABLED_UPDATE:
                    $id = $task['data']['id'];
                    try {
                        $customerAccount = $this->entityManager->getRepository(CustomerAccount::class)->find((int) $id);
                        if (null !== $customerAccount) {
                            $this->customerAccountPortalEnableUpdater->update($customerAccount);

                            $endMessageLog = \sprintf('[%s] Done JOB %s. %s of %s', (new \DateTime())->format('r'), $job->getId(), $task['data']['count'], $task['data']['maxCount']);
                        } else {
                            throw new \Exception('Customer Account Not Found');
                        }
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage().', customer id: '.$id;
                    }
                    break;
                case JobType::SCHEDULE_CUSTOMER_PORTAL_DISABLE:
                    try {
                        if ('iswitch' === $this->profile) {
                            //schedule customer portal disable
                            $disableDate = new \DateTime($task['data']['date'], $this->timezone);
                            $customerPortalDisableJob = new DisqueJob([
                                'data' => [
                                    'date' => $disableDate->format('j M Y'),
                                ],
                                'type' => JobType::CUSTOMER_PORTAL_DISABLE,
                            ]);

                            $this->contractQueue->push($customerPortalDisableJob);
                            $action = 'Pushed';
                            $logDate = (new \DateTime())->format('r');

                            $io->text(\sprintf('%s %s for at %s', $action, JobType::CUSTOMER_PORTAL_DISABLE, $logDate));
                            $this->logger->info(\sprintf('%s %s for at %s', $action, JobType::CUSTOMER_PORTAL_DISABLE, $logDate));
                            //schedule customer portal disable

                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::MOTHER_OF_CONTRACT:
                    //run the function
                    $jobDate = new \DateTime($task['data']['date'], $this->timezone);

                    if (isset($task['data']['only-future-jobs'])) {
                        $queueFutureJobsOnly = (bool) $task['data']['only-future-jobs'];
                    }

                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:cron:queue-cron-job-schedule --queue=contracts --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:cron:queue-cron-job-schedule --queue=contracts --env=worker';
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
                        'type' => JobType::MOTHER_OF_CONTRACT,
                    ]);

                    //convert to utc for checking
                    $nextDate->setTimezone($utcTimezone);
                    if ($nextDate <= new \DateTime()) {
                        $this->contractQueue->push($nextDayJob);
                        $action = 'Pushed';
                        $logDate = (new \DateTime())->format('r');
                    } else {
                        $jobTTL = 24.5 * 60 * 60;
                        $this->contractQueue->schedule($nextDayJob, $nextDate, ['ttl' => (int) $jobTTL]);
                        $action = 'Scheduled';
                        $logDate = $nextDate->format('r');
                    }
                    $io->text(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_CONTRACT, $logDate));
                    $this->logger->info(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_CONTRACT, $logDate));
                    //spawn next day cron job

                    $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
            }

            if ($error) {
                // nack the job
                $this->contractQueue->failed($job);
                $io->error($errorMessageLog);
                $this->logger->error($errorMessageLog);
            } else {
                // ack the job
                $this->contractQueue->processed($job);
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
