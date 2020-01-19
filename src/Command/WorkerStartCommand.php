<?php

declare(strict_types=1);

namespace App\Command;

use App\Disque\JobType;
use App\Document\Contract;
use App\Entity\AffiliateProgram;
use App\Entity\AffiliateProgramTransactionFetchHistory;
use App\Entity\ApplicationRequest;
use App\Entity\CustomerAccount;
use App\Entity\CustomerAccountAffiliateProgramUrl;
use App\Entity\QuantitativeValue;
use App\Entity\SmsActivity;
use App\Entity\WithdrawCreditsAction;
use App\Enum\AffiliateCommissionStatus;
use App\Enum\AffiliateWebServicePartner;
use App\Enum\ApplicationRequestType;
use App\Enum\DocumentType;
use App\Enum\PaymentMode;
use App\Enum\URLStatus;
use App\Model\AffiliateProgramCommissionConversionCalculator;
use App\Model\PartnerCommissionProcessor;
use App\Model\ReportGenerator;
use App\Model\SmsUpdater;
use App\Model\TariffRateUpdater;
use App\WebService\Affiliate\ClientFactory as AffiliateClientFactory;
use App\WebService\Affiliate\DummyClient;
use App\WebService\Billing\ClientInterface as WebServiceBillingClient;
use App\WebService\SMS\ClientInterface as SMSClient;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Schemes\Http as HttpUri;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManagerInterface;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class WorkerStartCommand extends Command
{
    /**
     * @var AffiliateClientFactory
     */
    private $affiliateClientFactory;

    /**
     * @var AffiliateProgramCommissionConversionCalculator
     */
    private $affiliateCommissionCalculator;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var string
     */
    private $emergencyWebhookUrl;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JWTManagerInterface
     */
    private $jwtManager;

    /**
     * @var ReportGenerator
     */
    private $reportGenerator;

    /**
     * @var DisqueQueue
     */
    private $reportsQueue;

    /**
     * @var DisqueQueue
     */
    private $webServicesQueue;

    /**
     * @var PartnerCommissionProcessor
     */
    private $partnerCommissionProcessor;

    /**
     * @var PhoneNumberUtil
     */
    private $phoneNumberUtil;

    /**
     * @var WebServiceBillingClient
     */
    private $webServiceClient;

    /**
     * @var SMSClient
     */
    private $smsClient;

    /**
     * @var string
     */
    private $profile;

    /**
     * @var SmsUpdater
     */
    private $smsUpdater;

    /**
     * @var TariffRateUpdater
     */
    private $tariffRateUpdater;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @var string
     */
    private $memoryLimit;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * WorkerStartCommand constructor.
     *
     * @param AffiliateClientFactory                         $affiliateClientFactory
     * @param AffiliateProgramCommissionConversionCalculator $affiliateCommissionCalculator
     * @param DocumentManager                                $documentManager
     * @param EntityManagerInterface                         $entityManager
     * @param LoggerInterface                                $logger
     * @param JWTManagerInterface                            $jwtManager
     * @param ReportGenerator                                $reportGenerator
     * @param DisqueQueue                                    $reportsQueue
     * @param DisqueQueue                                    $webServicesQueue
     * @param PartnerCommissionProcessor                     $partnerCommissionProcessor
     * @param PhoneNumberUtil                                $phoneNumberUtil
     * @param WebServiceBillingClient                        $webServiceClient
     * @param SMSClient                                      $smsClient
     * @param string                                         $profile
     * @param SmsUpdater                                     $smsUpdater
     * @param TariffRateUpdater                              $tariffRateUpdater
     * @param string                                         $documentConverterHost
     * @param string                                         $timezone
     * @param string                                         $emergencyWebhookUrl
     * @param string                                         $memoryLimit
     */
    public function __construct(AffiliateClientFactory $affiliateClientFactory, AffiliateProgramCommissionConversionCalculator $affiliateCommissionCalculator, DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger, JWTManagerInterface $jwtManager, ReportGenerator $reportGenerator, DisqueQueue $reportsQueue, DisqueQueue $webServicesQueue, PartnerCommissionProcessor $partnerCommissionProcessor, PhoneNumberUtil $phoneNumberUtil, WebServiceBillingClient $webServiceClient, SMSClient $smsClient, string $profile, SmsUpdater $smsUpdater, TariffRateUpdater $tariffRateUpdater, string $documentConverterHost, string $timezone, string $emergencyWebhookUrl, string $memoryLimit)
    {
        parent::__construct();

        $this->affiliateClientFactory = $affiliateClientFactory;
        $this->affiliateCommissionCalculator = $affiliateCommissionCalculator;
        $this->documentManager = $documentManager;
        $this->emergencyWebhookUrl = $emergencyWebhookUrl;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->jwtManager = $jwtManager;
        $this->reportGenerator = $reportGenerator;
        $this->reportsQueue = $reportsQueue;
        $this->webServicesQueue = $webServicesQueue;
        $this->partnerCommissionProcessor = $partnerCommissionProcessor;
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->webServiceClient = $webServiceClient;
        $this->smsClient = $smsClient;
        $this->profile = $profile;
        $this->smsUpdater = $smsUpdater;
        $this->tariffRateUpdater = $tariffRateUpdater;
        $this->documentConverterHost = $documentConverterHost;
        $this->client = new GuzzleClient();
        $this->timezone = new \DateTimeZone($timezone);
        $this->memoryLimit = $memoryLimit;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:worker:start')
            ->setDescription('Starts the worker for a specified queue.')
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Start worker for which queue?', null)
            ->setHelp(<<<'EOF'
The %command.name% command starts the worker.
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

        $queueName = $input->getOption('queue');

        if (null !== $queueName) {
            $io->success(\sprintf('Worker ready to accept tasks in the %s queue.', $queueName));
            $io->comment('Stop the worker with CONTROL-C.');
        } else {
            $io->error('Please specify a queue name with option --queue');
        }

        if ('reports' === $queueName) {
            while ($job = $this->reportsQueue->pull()) {
                if (!$job instanceof DisqueJob) {
                    throw new \UnexpectedValueException();
                }
                $task = $job->getBody();

                if ($job->getNacks() > 0 || $job->getAdditionalDeliveries() > 0) {
                    $this->reportsQueue->processed($job);

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

                //log start of job
                $io->text(\sprintf('[%s] Running JOB %s.', (new \DateTime())->format('r'), $job->getId()));
                $io->text(\json_encode($task, JSON_PRETTY_PRINT));
                $this->logger->info('Running JOB '.\json_encode($task, JSON_PRETTY_PRINT));

                switch ($task['type']) {
                    case JobType::APPLICATION_REQUEST_REPORT_GENERATE:
                        $reportParams = $task['data']['params'];
                        $recipient = $task['data']['recipient'];
                        $documentId = $task['data']['documentId'] ?? null;
                        $serialisedParams = \escapeshellarg(\serialize($reportParams));

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:generate:report --type=2 --env=worker ', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:generate:report --type=2 --env=worker ';
                        }
                        $command .= "--data={$serialisedParams} --documentId={$documentId} --recipient={$recipient}";

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $error = true;
                            $errorMessageLog = $process->getErrorOutput();
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                        break;
                    case JobType::GENERATE_PARTNER_CONTRACT_APPLICATION_REPORT:
                        //run the function
                        $endDate = new \DateTime($task['data']['endDate']);
                        $startDate = new \DateTime($task['data']['startDate']);
                        $allReportData = $this->reportGenerator->createPartnerContractApplicationReport($startDate, $endDate);
                        $allDraftInProgressVoidedReport = $this->reportGenerator->createPartnerContractApplicationReport(null, $endDate, 'draft_in_progress_voided', \sprintf('%s%s_%s.xlsx', 'Partnership Report_', 'ALL', 'DraftInProgressVoided'));
                        $allReportData[] = \array_pop($allDraftInProgressVoidedReport);

                        if (!empty($allReportData)) {
                            $reports = $this->reportGenerator->convertDataToInternalDocument($allReportData, DocumentType::PARTNER_CONTRACT_APPLICATION_REQUEST_REPORT);

                            // if report is generated for 1st Nov, current date time should be 2nd Nov
                            // scheduled date is 8am on 2nd Nov
                            $scheduleDate = clone $startDate;
                            $scheduleDate->modify('+1 day')->setTime(8, 0, 0);
                            $scheduleDate->setTimezone(new \DateTimeZone('UTC'));

                            $this->reportGenerator->queueEmailJob($reports, [], $scheduleDate);
                        } else {
                            $io->text('No reports generated?');
                            $this->logger->info('No reports generated?');
                        }

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        break;
                    case JobType::PARTNER_GENERATE_COMMISSION_STATEMENT:
                        //run the function
                        $errorMessageLog = $this->partnerCommissionProcessor->generatePartnerCommissionStatement($task['data']);

                        if (null !== $errorMessageLog) {
                            $error = true;
                        }
                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        break;
                    case JobType::CONTRACT_REPORT_GENERATE:
                        $reportParams = $task['data']['params'];
                        $recipient = $task['data']['recipient'];
                        $documentId = $task['data']['documentId'] ?? null;
                        $serialisedParams = \escapeshellarg(\serialize($reportParams));

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:generate:report --type=3 --env=worker ', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:generate:report --type=3 --env=worker ';
                        }
                        $command .= "--data={$serialisedParams} --documentId={$documentId} --recipient={$recipient}";

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $error = true;
                            $errorMessageLog = $process->getErrorOutput();
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                        break;
                    case JobType::CREDITS_ACTION_REPORT_GENERATE:
                        $reportParams = $task['data']['params'];
                        $recipient = $task['data']['recipient'];
                        $documentId = $task['data']['documentId'] ?? null;
                        $serialisedParams = \escapeshellarg(\serialize($reportParams));

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:generate:report --type=4 --env=worker ', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:generate:report --type=4 --env=worker ';
                        }
                        $command .= "--data={$serialisedParams} --documentId={$documentId} --recipient={$recipient}";

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $error = true;
                            $errorMessageLog = $process->getErrorOutput();
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                        break;
                    case JobType::CUSTOMER_ACCOUNT_RELATIONSHIP_REPORT_GENERATE:
                        $reportParams = $task['data']['params'];
                        $recipient = $task['data']['recipient'];
                        $documentId = $task['data']['documentId'] ?? null;
                        $serialisedParams = \escapeshellarg(\serialize($reportParams));

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:generate:report --type=5 --env=worker ', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:generate:report --type=5 --env=worker ';
                        }
                        $command .= "--data={$serialisedParams} --documentId={$documentId} --recipient={$recipient}";

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $error = true;
                            $errorMessageLog = $process->getErrorOutput();
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                        break;
                    case JobType::CUSTOMER_ACCOUNT_REPORT_GENERATE:
                        $reportParams = $task['data']['params'];
                        $recipient = $task['data']['recipient'];
                        $documentId = $task['data']['documentId'] ?? null;
                        $serialisedParams = \escapeshellarg(\serialize($reportParams));

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:generate:report --type=6 --env=worker ', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:generate:report --type=6 --env=worker ';
                        }
                        $command .= "--data={$serialisedParams} --documentId={$documentId} --recipient={$recipient}";

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $error = true;
                            $errorMessageLog = $process->getErrorOutput();
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                        break;
                    case JobType::LEAD_REPORT_GENERATE:
                        $reportParams = $task['data']['params'];
                        $recipient = $task['data']['recipient'];
                        $documentId = $task['data']['documentId'] ?? null;
                        $serialisedParams = \escapeshellarg(\serialize($reportParams));

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:generate:report --type=7 --env=worker ', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:generate:report --type=7 --env=worker ';
                        }
                        $command .= "--data={$serialisedParams} --documentId={$documentId} --recipient={$recipient}";

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $error = true;
                            $errorMessageLog = $process->getErrorOutput();
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                        break;
                    case JobType::ORDER_REPORT_GENERATE:
                        $reportParams = $task['data']['params'];
                        $recipient = $task['data']['recipient'];
                        $documentId = $task['data']['documentId'] ?? null;
                        $serialisedParams = \escapeshellarg(\serialize($reportParams));

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:generate:report --type=8 --env=worker ', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:generate:report --type=8 --env=worker ';
                        }
                        $command .= "--data={$serialisedParams} --documentId={$documentId} --recipient={$recipient}";

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $error = true;
                            $errorMessageLog = $process->getErrorOutput();
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                        break;
                    case JobType::TICKET_REPORT_GENERATE:
                        $reportParams = $task['data']['params'];
                        $recipient = $task['data']['recipient'];
                        $documentId = $task['data']['documentId'] ?? null;
                        $serialisedParams = \escapeshellarg(\serialize($reportParams));

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:generate:report --type=9 --env=worker ', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:generate:report --type=9 --env=worker ';
                        }
                        $command .= "--data={$serialisedParams} --documentId={$documentId} --recipient={$recipient}";

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $error = true;
                            $errorMessageLog = $process->getErrorOutput();
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                        break;
                    case JobType::USER_REPORT_GENERATE:
                        $reportParams = $task['data']['params'];
                        $recipient = $task['data']['recipient'];
                        $documentId = $task['data']['documentId'] ?? null;
                        $serialisedParams = \escapeshellarg(\serialize($reportParams));

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:generate:report --type=10 --env=worker ', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:generate:report --type=10 --env=worker ';
                        }
                        $command .= "--data={$serialisedParams} --documentId={$documentId} --recipient={$recipient}";

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            $error = true;
                            $errorMessageLog = $process->getErrorOutput();
                        } else {
                            $io->text($process->getOutput());
                            $this->logger->info($process->getOutput());
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        }
                        break;
                    default:
                        $error = true;
                        $errorMessageLog = \sprintf('[%s] Wrong Queue? Fail JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        break;
                }

                if (true === $error) {
                    // nack the job
                    $this->reportsQueue->failed($job);
                    $io->error($errorMessageLog);
                    $this->logger->error($errorMessageLog);
                } else {
                    // ack the job
                    $this->reportsQueue->processed($job);
                    $io->text($endMessageLog);
                    $this->logger->info($endMessageLog);
                }

                $tempFile = null;
                $this->entityManager->clear();
                $io->newLine();
            }
        } elseif ('web_services' === $queueName) {
            while ($job = $this->webServicesQueue->pull()) {
                if (!$job instanceof DisqueJob) {
                    throw new \UnexpectedValueException();
                }
                $task = $job->getBody();

                if ($job->getNacks() > 0 || $job->getAdditionalDeliveries() > 0) {
                    $this->webServicesQueue->processed($job);

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

                //log start of job
                $io->text(\sprintf('[%s] Running JOB %s.', (new \DateTime())->format('r'), $job->getId()));
                $io->text(\json_encode($task, JSON_PRETTY_PRINT));
                $this->logger->info('Running JOB '.\json_encode($task, JSON_PRETTY_PRINT));

                switch ($task['type']) {
                    case JobType::AFFILIATE_PROGRAM_PROCESS_FETCH_TRANSACTION:
                        try {
                            $fetchHistory = $this->entityManager->getRepository(AffiliateProgramTransactionFetchHistory::class)->find($task['data']['id']);

                            if (null !== $fetchHistory) {
                                // run the function
                                if ($fetchHistory->getPendingConversions()->getValue() > 0) {
                                    $affiliateWebServiceClient = $this->affiliateClientFactory->getClient($fetchHistory->getProvider()->getValue());

                                    if (!$affiliateWebServiceClient instanceof DummyClient) {
                                        $io->success(\sprintf('Affiliate Client class for %s, %s found.', $fetchHistory->getProvider()->getValue(), \get_class($affiliateWebServiceClient)));
                                        $io->comment('Fetching conversion data...');

                                        $result = $affiliateWebServiceClient->getConversionDataByDate($fetchHistory->getStartDate(), $fetchHistory->getEndDate());
                                        $transactions = $affiliateWebServiceClient->normalizeConversionData($result);

                                        $pendingCount = 0;
                                        foreach ($transactions as $transaction) {
                                            if (AffiliateCommissionStatus::PENDING === $transaction['commissionStatus']->getValue()) {
                                                ++$pendingCount;
                                            }
                                        }

                                        $fetchHistory->setPendingConversions(new QuantitativeValue((string) $pendingCount));
                                        $this->affiliateCommissionCalculator->processData($transactions, $affiliateWebServiceClient->getProviderName());
                                    }
                                } else {
                                    $io->text('No more pending conversions.');
                                    $this->logger->info('No more pending conversions.');
                                }

                                //log end of job
                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('No AffiliateProgramTransactionFetchHistory found.');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::AFFILIATE_PROGRAM_QUEUE_FETCH_TRANSACTION:
                        try {
                            $fetchStartDate = new \DateTime();
                            $fetchStartDate->modify('-1 month');

                            $qb = $this->entityManager->getRepository(AffiliateProgramTransactionFetchHistory::class)->createQueryBuilder('fetchHistory');
                            $expr = $qb->expr();

                            $fetchHistories = $qb->where($expr->eq('fetchHistory.provider', ':provider'))
                                ->andWhere($expr->orX(
                                    $expr->gte('fetchHistory.pendingConversions.value', ':pendingConversions'),
                                    $expr->gte('fetchHistory.startDate', ':fetchStartDate')
                                ))
                                ->setParameter('provider', $task['data']['provider'])
                                ->setParameter('fetchStartDate', $fetchStartDate->format('c'))
                                ->setParameter('pendingConversions', 1)
                                ->orderBy('fetchHistory.startDate', 'ASC')
                                ->getQuery()
                                ->getResult();

                            foreach ($fetchHistories as $fetchHistory) {
                                $newJob = new DisqueJob([
                                    'data' => [
                                        'id' => $fetchHistory->getId(),
                                    ],
                                    'type' => JobType::AFFILIATE_PROGRAM_PROCESS_FETCH_TRANSACTION,
                                ]);

                                $this->webServicesQueue->push($newJob);
                            }

                            // queue new job for the past day
                            $endDate = new \DateTime();
                            $endDate->setTimezone($this->timezone);

                            $endDate->modify('-1 day');
                            $endDate->setTime(23, 59, 59);

                            $qb = $this->entityManager->getRepository(AffiliateProgramTransactionFetchHistory::class)->createQueryBuilder('fetchHistory');
                            $expr = $qb->expr();

                            $lastDate = $qb->select($expr->max('fetchHistory.endDate'))
                                ->where($expr->eq('fetchHistory.provider', ':provider'))
                                ->setParameter('provider', $task['data']['provider'])
                                ->getQuery()
                                ->getSingleScalarResult();

                            if (null !== $lastDate) {
                                $startDate = new \DateTime($lastDate);
                                $startDate->setTimezone($this->timezone);
                                $startDate->setTime(0, 0, 0);
                            } else {
                                $startDate = clone $endDate;
                                $startDate->setTime(0, 0, 0);
                            }

                            // reset to UTC
                            $startDate->setTimezone(new \DateTimeZone('UTC'));
                            $endDate->setTimezone(new \DateTimeZone('UTC'));

                            $startTimestamp = \strtotime($startDate);
                            $endTimestamp = \strtotime($endDate);

                            // must have at least an hour difference between start and end date
                            if (($endTimestamp - $startTimestamp) >= 3600) {
                                $fetchHistory = new AffiliateProgramTransactionFetchHistory();
                                $fetchHistory->setEndDate($endDate);
                                $fetchHistory->setProvider(new AffiliateWebServicePartner($task['data']['provider']));
                                $fetchHistory->setStartDate($startDate);

                                $this->entityManager->persist($fetchHistory);
                                $this->entityManager->flush();

                                $newJob = new DisqueJob([
                                    'data' => [
                                        'id' => $fetchHistory->getId(),
                                    ],
                                    'type' => JobType::AFFILIATE_PROGRAM_PROCESS_FETCH_TRANSACTION,
                                ]);

                                $this->webServicesQueue->push($newJob);
                            }

                            // queue next queue job
                            $scheduledTime = new \DateTime();
                            $scheduledTime->modify('+24 hours');
                            $nextQueueJob = new DisqueJob([
                                'data' => [
                                    'provider' => $task['data']['provider'],
                                ],
                                'type' => JobType::AFFILIATE_PROGRAM_QUEUE_FETCH_TRANSACTION,
                            ]);
                            $jobTTL = 24.5 * 60 * 60;
                            $this->webServicesQueue->schedule($nextQueueJob, $scheduledTime, ['ttl' => (int) $jobTTL]);

                            //log end of job
                            $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::AFFILIATE_PROGRAM_GENERATE_URL:
                        try {
                            $affiliateProgram = $this->entityManager->getRepository(AffiliateProgram::class)->find($task['data']['affiliateProgram']);
                            $affiliateProgramUrl = $this->entityManager->getRepository(CustomerAccountAffiliateProgramUrl::class)->findOneBy($task['data']);
                            $customer = $this->entityManager->getRepository(CustomerAccount::class)->find($task['data']['customer']);

                            if (null === $affiliateProgramUrl && null !== $customer && null !== $affiliateProgram) {
                                // run the function
                                $trackingUrl = '';

                                // for now only hasoffers
                                if (AffiliateWebServicePartner::LAZADA_HASOFFERS === $affiliateProgram->getProvider()->getValue()) {
                                    $affiliateWebServiceClient = $this->affiliateClientFactory->getClient($affiliateProgram->getProvider()->getValue());

                                    $trackingUrl = $affiliateWebServiceClient->generateTrackingUrl('', [
                                        'customerAccountNumber' => $customer->getAccountNumber(),
                                        'programNumber' => $affiliateProgram->getProgramNumber(),
                                    ]);
                                }

                                if ('' !== $trackingUrl) {
                                    $affiliateProgramUrl = new CustomerAccountAffiliateProgramUrl();
                                    $affiliateProgramUrl->setAffiliateProgram($affiliateProgram);
                                    $affiliateProgramUrl->setCustomer($customer);

                                    // Disable safeguard for URL status
                                    // $affiliateProgramUrl->setStatus(new URLStatus(URLStatus::INACTIVE));
                                    // Make it active right away, pay $$ to enable safeguard
                                    $affiliateProgramUrl->setStatus(new URLStatus(URLStatus::ACTIVE));
                                    $affiliateProgramUrl->setUrl($trackingUrl);

                                    $this->entityManager->persist($affiliateProgramUrl);
                                    $this->entityManager->flush();

                                    $newJob = new DisqueJob([
                                        'data' => [
                                            'id' => $affiliateProgramUrl->getId(),
                                        ],
                                        'type' => JobType::AFFILIATE_PROGRAM_GENERATED_URL,
                                    ]);

                                    $scheduledTime = new \DateTime();
                                    $scheduledTime->modify('+2 minutes');

                                    $this->webServicesQueue->schedule($newJob, $scheduledTime);
                                }

                                //log end of job
                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('Failed to generate Affiliate Program URL.');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::AFFILIATE_PROGRAM_GENERATED_URL:
                        try {
                            $affiliateProgramUrl = $this->entityManager->getRepository(CustomerAccountAffiliateProgramUrl::class)->find($task['data']['id']);

                            if (null !== $affiliateProgramUrl && URLStatus::ACTIVE !== $affiliateProgramUrl->getStatus()->getValue()) {
                                //run the function
                                $uri = HttpUri::createFromString($affiliateProgramUrl->getUrl());
                                $response = $this->client->request('HEAD', $uri);

                                if (200 === $response->getStatusCode()) {
                                    $affiliateProgramUrl->setStatus(new URLStatus(URLStatus::ACTIVE));

                                    $this->entityManager->persist($affiliateProgramUrl);
                                    $this->entityManager->flush();
                                } else {
                                    $job = new DisqueJob([
                                        'data' => [
                                            'id' => $affiliateProgramUrl->getId(),
                                        ],
                                        'type' => JobType::AFFILIATE_PROGRAM_GENERATED_URL,
                                    ]);

                                    $scheduledTime = new \DateTime();
                                    $scheduledTime->modify('+2 minutes');

                                    $this->webServicesQueue->schedule($job, $scheduledTime);
                                }

                                //log end of job
                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('Affiliate Program URL not found.');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::APPLICATION_REQUEST_SUBMIT:
                        try {
                            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->find($task['data']['id']);

                            if (null !== $applicationRequest) {
                                //run the function
                                $this->webServiceClient->submitApplicationRequest($applicationRequest, null);

                                if (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
                                    // @todo do better.
                                    if ('unionpower' === $this->profile) {
                                        $message = \sprintf('Dear Customer, thank you for signing up with Union Power. Your application %s is currently processing, and additional information will be sent by email.', $applicationRequest->getApplicationRequestNumber());
                                        $recipient = null;
                                        $sender = 'UPCustCare';

                                        if (null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getContactPerson()->getPersonDetails()) {
                                            foreach ($applicationRequest->getContactPerson()->getPersonDetails()->getContactPoints() as $contactPoint) {
                                                if (\count($contactPoint->getMobilePhoneNumbers()) > 0) {
                                                    $recipient = \array_values(\array_slice($contactPoint->getMobilePhoneNumbers(), -1))[0];
                                                    $recipient = $this->phoneNumberUtil->format($recipient, PhoneNumberFormat::E164);
                                                    break;
                                                }
                                            }
                                        }

                                        if (null !== $recipient) {
                                            $smsHistoryData = $this->smsClient->send($recipient, $message, $sender);
                                            $this->smsUpdater->create($smsHistoryData);
                                        } else {
                                            $io->text(\sprintf('No mobile number found for %s.', $applicationRequest->getApplicationRequestNumber()));
                                            $this->logger->info(\sprintf('No mobile number found for %s.', $applicationRequest->getApplicationRequestNumber()));
                                        }
                                    }
                                }

                                //log end of job
                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('Application request not found');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::CONTRACT_UPDATE_PAYMENT_MODE:
                        try {
                            $contract = $this->entityManager->getRepository(\App\Entity\Contract::class)->find($task['data']['id']);

                            if (null !== $contract) {
                                $contract->setPaymentMode(new PaymentMode($task['data']['paymentMode']));

                                $this->entityManager->persist($contract);
                                $this->entityManager->flush();

                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('Contract not found');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::CUSTOMER_ACCOUNT_CONTACT_UPDATE:
                        try {
                            $id = $task['data']['id'];
                            $previousName = $task['data']['previousName'];

                            $customerAccount = $this->entityManager->getRepository(CustomerAccount::class)->find($id);
                            if (null !== $customerAccount) {
                                $this->webServiceClient->updateCustomerContact($customerAccount, $previousName);

                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('Customer Account not found');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::WITHDRAW_CREDITS_ACTION_SUBMIT:
                        try {
                            $withdrawCreditsAction = $this->entityManager->getRepository(WithdrawCreditsAction::class)->find($task['data']['id']);

                            if (null !== $withdrawCreditsAction) {
                                //run the function
                                $this->webServiceClient->submitWithdrawCreditsAction($withdrawCreditsAction);

                                //log end of job
                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('Withdraw credits action not found');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::CUSTOMER_ACCOUNT_SMS_CUSTOMER_SERVICE_FEEDBACK_ACTIVITY_CREATED:
                    case JobType::LEAD_SMS_CUSTOMER_SERVICE_FEEDBACK_ACTIVITY_CREATED:
                        try {
                            $activity = $this->entityManager->getRepository(SmsActivity::class)->find($task['data']['id']);

                            if (null !== $activity) {
                                // @todo do better.
                                $message = $activity->getText();
                                $recipient = $this->phoneNumberUtil->format($activity->getRecipientMobileNumber(), PhoneNumberFormat::E164);
                                $sender = null;

                                if (null !== $message) {
                                    $smsHistoryData = $this->smsClient->send($recipient, $message, $sender);
                                    $this->smsUpdater->createActivitySmsHistory($activity, $this->smsUpdater->create($smsHistoryData));
                                } else {
                                    $io->text(\sprintf('No configuration for SMS profile: %s.', $this->profile));
                                    $this->logger->info(\sprintf('No configuration for SMS profile: %s.', $this->profile));
                                }

                                //log end of job
                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('SmsActivity not found');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::APPLICATION_REQUEST_COMPLETED:
                        try {
                            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->find($task['data']['id']);

                            if (null !== $applicationRequest) {
                                // @todo do better.
                                if ('unionpower' === $this->profile) {
                                    $message = "Dear Customer, \nWelcome to Union Power! Your electricity account transfer to Union Power is successful. Please check your email for your login details. Thank you.";
                                    $recipient = null;
                                    $sender = 'UPCustCare';

                                    if (null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getContactPerson()->getPersonDetails()) {
                                        foreach ($applicationRequest->getContactPerson()->getPersonDetails()->getContactPoints() as $contactPoint) {
                                            if (\count($contactPoint->getMobilePhoneNumbers()) > 0) {
                                                $recipient = \array_values(\array_slice($contactPoint->getMobilePhoneNumbers(), -1))[0];
                                                $recipient = $this->phoneNumberUtil->format($recipient, PhoneNumberFormat::E164);
                                                break;
                                            }
                                        }
                                    }

                                    if (null !== $recipient) {
                                        $smsHistoryData = $this->smsClient->send($recipient, $message, $sender);
                                        $this->smsUpdater->create($smsHistoryData);
                                    } else {
                                        $io->text(\sprintf('No mobile number found for %s.', $applicationRequest->getApplicationRequestNumber()));
                                        $this->logger->info(\sprintf('No mobile number found for %s.', $applicationRequest->getApplicationRequestNumber()));
                                    }
                                }

                                //log end of job
                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('Application request not found');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::APPLICATION_REQUEST_CANCELLED:
                    case JobType::APPLICATION_REQUEST_REJECTED:
                        try {
                            $applicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->find($task['data']['id']);

                            if (null !== $applicationRequest) {
                                // @todo do better.
                                if ('unionpower' === $this->profile) {
                                    $message = 'Dear Customer, we regret that your electricity account transfer to Union Power was unsuccessful. Please contact us at +65 6858 5555 for any enquiries.';
                                    $recipient = null;
                                    $sender = 'UPCustCare';

                                    if (null !== $applicationRequest->getContactPerson() && null !== $applicationRequest->getContactPerson()->getPersonDetails()) {
                                        foreach ($applicationRequest->getContactPerson()->getPersonDetails()->getContactPoints() as $contactPoint) {
                                            if (\count($contactPoint->getMobilePhoneNumbers()) > 0) {
                                                $recipient = \array_values(\array_slice($contactPoint->getMobilePhoneNumbers(), -1))[0];
                                                $recipient = $this->phoneNumberUtil->format($recipient, PhoneNumberFormat::E164);
                                                break;
                                            }
                                        }
                                    }

                                    if (null !== $recipient) {
                                        $smsHistoryData = $this->smsClient->send($recipient, $message, $sender);
                                        $this->smsUpdater->create($smsHistoryData);
                                    } else {
                                        $io->text(\sprintf('No mobile number found for %s.', $applicationRequest->getApplicationRequestNumber()));
                                        $this->logger->info(\sprintf('No mobile number found for %s.', $applicationRequest->getApplicationRequestNumber()));
                                    }
                                }

                                //log end of job
                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('Application request not found');
                            }
                        } catch (\Exception $ex) {
                            $error = true;
                            $errorMessageLog = $ex->getMessage();
                        }
                        break;
                    case JobType::FIX_DAMN_BRIDGE:
                        try {
                            $applicationRequestNumber = $task['data']['applicationRequestNumber'] ?? null;
                            $errorMessageLog = '';
                            $requeue = false;
                            $runCount = 1;

                            if (isset($task['data']['runCount'])) {
                                $runCount = ((int) $task['data']['runCount']) + 1;
                            }

                            if (null !== $applicationRequestNumber) {
                                $tempApplicationRequest = $this->entityManager->getRepository(ApplicationRequest::class)->findOneBy([
                                    'temporaryNumber' => $applicationRequestNumber,
                                ]);

                                $contractApplicationRequest = $this->documentManager->getRepository(Contract::class)->findOneBy([
                                    'tempId' => $applicationRequestNumber,
                                ]);

                                if (null === $tempApplicationRequest) {
                                    $error = true;
                                    $errorMessageLog .= 'Application request not found in PostgreSQL. ';
                                }

                                if (null === $contractApplicationRequest) {
                                    $error = true;
                                    $requeue = true;
                                    $errorMessageLog .= 'Contract not found in MongoDB. ';
                                }

                                // wtf phpstan not smart enough
                                if (false === $error && null !== $contractApplicationRequest && null !== $tempApplicationRequest) {
                                    if (null !== $contractApplicationRequest->getApplicationRequestNumber() && $tempApplicationRequest->getApplicationRequestNumber() !== $contractApplicationRequest->getApplicationRequestNumber()) {
                                        $tempApplicationRequest->setApplicationRequestNumber($contractApplicationRequest->getApplicationRequestNumber());
                                        $tempApplicationRequest->setBridgeId($contractApplicationRequest->getId());
                                        $this->entityManager->persist($tempApplicationRequest);
                                        $this->entityManager->flush();
                                    }
                                }

                                if ($runCount < 10 && true === $requeue && null !== $tempApplicationRequest) {
                                    $io->text(\sprintf('Queueing for run #%d.', $runCount));
                                    $this->logger->info(\sprintf('Queueing for run #%d.', $runCount));
                                    $this->webServicesQueue->schedule(new DisqueJob([
                                        'data' => [
                                            'applicationRequestNumber' => $tempApplicationRequest->getTemporaryNumber(),
                                            'runCount' => $runCount,
                                        ],
                                        'type' => JobType::FIX_DAMN_BRIDGE,
                                    ]), (new \DateTime())->modify('+10 seconds'));
                                }

                                //log end of job
                                $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                            } else {
                                throw new \Exception('Application request number??');
                            }
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
                    $this->webServicesQueue->failed($job);
                    $io->error($errorMessageLog);
                    $this->logger->error($errorMessageLog);
                } else {
                    // ack the job
                    $this->webServicesQueue->processed($job);
                    $io->text($endMessageLog);
                    $this->logger->info($endMessageLog);
                }

                $tempFile = null;
                $this->entityManager->clear();
                $io->newLine();
            }
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
