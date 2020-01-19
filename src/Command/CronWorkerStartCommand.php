<?php

declare(strict_types=1);

namespace App\Command;

use App\Disque\JobType;
use App\Entity\MaintenanceConfiguration;
use App\Enum\MaintenanceConfigurationStatus;
use App\Model\CreditsWithdrawalStatusUpdater;
use App\Model\CustomerBlacklistUpdater;
use App\Model\PartnerCommissionProcessor;
use App\Model\TariffRateUpdater;
use App\WebService\Billing\ClientInterface as WebServiceBillingClient;
use App\WebService\Billing\Enum\DownloadFileType;
use App\WebService\Billing\Enum\UploadFileType;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class CronWorkerStartCommand extends Command
{
    /**
     * @var CreditsWithdrawalStatusUpdater
     */
    private $creditsWithdrawalStatusUpdater;

    /**
     * @var CustomerBlacklistUpdater
     */
    private $customerBlacklistUpdater;

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
     * @var DisqueQueue
     */
    private $reportsQueue;

    /**
     * @var DisqueQueue
     */
    private $cronQueue;

    /**
     * @var PartnerCommissionProcessor
     */
    private $partnerCommissionProcessor;

    /**
     * @var WebServiceBillingClient
     */
    private $webServiceClient;

    /**
     * @var TariffRateUpdater
     */
    private $tariffRateUpdater;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var string
     */
    private $memoryLimit;

    /**
     * @param CreditsWithdrawalStatusUpdater $creditsWithdrawalStatusUpdater
     * @param CustomerBlacklistUpdater       $customerBlacklistUpdater
     * @param EntityManagerInterface         $entityManager
     * @param LoggerInterface                $logger
     * @param DisqueQueue                    $reportsQueue
     * @param DisqueQueue                    $cronQueue
     * @param PartnerCommissionProcessor     $partnerCommissionProcessor
     * @param WebServiceBillingClient        $webServiceClient
     * @param TariffRateUpdater              $tariffRateUpdater
     * @param string                         $documentConverterHost
     * @param string                         $timezone
     * @param string                         $emergencyWebhookUrl
     * @param string                         $memoryLimit
     */
    public function __construct(CreditsWithdrawalStatusUpdater $creditsWithdrawalStatusUpdater, CustomerBlacklistUpdater $customerBlacklistUpdater, EntityManagerInterface $entityManager, LoggerInterface $logger, DisqueQueue $reportsQueue, DisqueQueue $cronQueue, PartnerCommissionProcessor $partnerCommissionProcessor, WebServiceBillingClient $webServiceClient, TariffRateUpdater $tariffRateUpdater, string $documentConverterHost, string $timezone, string $emergencyWebhookUrl, string $memoryLimit)
    {
        parent::__construct();

        $this->creditsWithdrawalStatusUpdater = $creditsWithdrawalStatusUpdater;
        $this->customerBlacklistUpdater = $customerBlacklistUpdater;
        $this->emergencyWebhookUrl = $emergencyWebhookUrl;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->reportsQueue = $reportsQueue;
        $this->cronQueue = $cronQueue;
        $this->partnerCommissionProcessor = $partnerCommissionProcessor;
        $this->webServiceClient = $webServiceClient;
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
            ->setName('app:cron-worker:start')
            ->setDescription('Starts the cron worker.')
            ->setHelp(<<<'EOF'
The %command.name% command starts the cron worker.
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

        $io->success('Worker ready to accept tasks in the cron queue.');
        $io->comment('Stop the worker with CONTROL-C.');

        while ($job = $this->cronQueue->pull()) {
            if (!$job instanceof DisqueJob) {
                throw new \UnexpectedValueException();
            }
            $task = $job->getBody();

            if ($job->getNacks() > 0 || $job->getAdditionalDeliveries() > 0) {
                $this->cronQueue->processed($job);

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
                case JobType::CRON_DOWNLOAD_CONTRACT_APPLICATION_RETURN:
                    try {
                        $date = new \DateTime($task['data']['date'], $this->timezone);
                        $this->webServiceClient->downloadXML($date, DownloadFileType::CONTRACT_APPLICATION_RETURN);
                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_GENERATE_PARTNER_CONTRACT_APPLICATION_REPORT:
                    try {
                        $reportJobDateTime = new \DateTime($task['data']['date'], $this->timezone);
                        $reportJobDateTime->setTime(0, 0, 0);

                        $reportStartDate = clone $reportJobDateTime;
                        $reportStartDate->modify('-1 day')->setTime(0, 0, 0);
                        $reportEndDate = clone $reportStartDate;
                        $reportEndDate->setTime(23, 59, 59);

                        $reportJob = new DisqueJob([
                            'data' => [
                                'endDate' => $reportEndDate->format('c'),
                                'startDate' => $reportStartDate->format('c'),
                            ],
                            'type' => JobType::GENERATE_PARTNER_CONTRACT_APPLICATION_REPORT,
                        ]);

                        $reportJobDateTime->setTimezone($utcTimezone);
                        if ($reportJobDateTime <= new \DateTime() && false === $queueFutureJobsOnly) {
                            $this->reportsQueue->push($reportJob);
                        } else {
                            $this->reportsQueue->schedule($reportJob, $reportJobDateTime);
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_CHECK_APPLICATION_REQUEST_STATUS_HISTORY:
                    try {
                        $jobDateTime = new \DateTime($task['data']['date'], $this->timezone);
                        $jobDateTime->modify('-1 day');
                        $dateParameter = $jobDateTime->format('Y-m-d');

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:generate-reconciliation-file --end-date=\'%s\' --start-date=\'%s\' --notify --env=worker', $this->memoryLimit, $dateParameter, $dateParameter);
                        } else {
                            $command = \sprintf('php bin/console app:web-service:generate-reconciliation-file --end-date=\'%s\' --start-date=\'%s\' --notify --env=worker', $dateParameter, $dateParameter);
                        }

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(600);
                        $process->run();

                        if (!$process->isSuccessful()) {
                            throw new \Exception($process->getErrorOutput());
                        }

                        $io->text($process->getOutput());
                        $this->logger->info($process->getOutput());

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_PARTNER_GENERATE_COMMISSION_STATEMENT:
                    try {
                        $utcStart = new \DateTime($task['data']['date'], $this->timezone);
                        $utcStart->setTime(0, 0, 0)->setTimezone($utcTimezone);

                        $utcEnd = clone $utcStart;
                        $utcEnd->setTime(23, 59, 59)->setTimezone($utcTimezone);

                        $commissionStatements = $this->partnerCommissionProcessor->getStatementsByDate($utcStart, $utcEnd);

                        foreach ($commissionStatements as $commissionStatement) {
                            $commissionJob = new DisqueJob([
                                'data' => [
                                    'commissionStatementId' => $commissionStatement->getId(),
                                    'endDateTimestamp' => $commissionStatement->getEndDate()->getTimestamp(),
                                    'partnerId' => $commissionStatement->getPartner()->getId(),
                                ],
                                'type' => JobType::PARTNER_GENERATE_COMMISSION_STATEMENT,
                            ]);

                            $jobDateTime = $commissionStatement->getEndDate();
                            if ($jobDateTime <= new \DateTime() && false === $queueFutureJobsOnly) {
                                $this->reportsQueue->push($commissionJob);
                            } else {
                                $this->reportsQueue->schedule($commissionJob, $jobDateTime);
                            }
                        }

                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_ORDER_BILL_REBATE_SUBMIT:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:submit-redeem-credits --date=\'%s\' --upload --env=worker', $this->memoryLimit, $task['data']['date']);
                        } else {
                            $command = \sprintf('php bin/console app:web-service:submit-redeem-credits --date=\'%s\' --upload --env=worker', $task['data']['date']);
                        }

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(600);
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
                case JobType::CRON_PROCESS_ACCOUNT_CLOSURE_APPLICATION:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-account-closure-xml --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-account-closure-xml --env=worker';
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
                case JobType::CRON_PROCESS_CONTRACT_APPLICATION:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-contract-application-xml --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-contract-application-xml --env=worker';
                        }

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(3600);
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
                case JobType::CRON_PROCESS_CONTRACT_RENEWAL_APPLICATION:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-contract-renewal-application-xml --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-contract-renewal-application-xml --env=worker';
                        }

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(600);
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
                case JobType::CRON_PROCESS_CUSTOMER_ACCOUNT_BLACKLIST_UPDATE:
                    try {
                        $date = new \DateTime($task['data']['date'], $this->timezone);
                        $tempFile = $this->webServiceClient->getCustomerBlackListXMLFile($date);

                        if (true === \is_resource($tempFile) && \filesize(\stream_get_meta_data($tempFile)['uri']) > 0) {
                            $io->text('Uploading to document converter...');

                            $baseDocumentConverterUri = HttpUri::createFromString($this->documentConverterHost);
                            $modifier = new AppendSegment('customer_blacklists/xml/create');
                            $documentConverterUri = $modifier->process($baseDocumentConverterUri);

                            $multipartContent = [
                                'headers' => [
                                    'User-Agent' => 'U-Centric API',
                                ],
                                'multipart' => [
                                    [
                                        'name' => 'file',
                                        'filename' => \uniqid().'.xml',
                                        'contents' => $tempFile,
                                    ],
                                ],
                            ];
                            $uploadResponse = $this->client->request('POST', $documentConverterUri, $multipartContent);
                            $uploadResult = \json_decode((string) $uploadResponse->getBody(), true);

                            if (200 === $uploadResponse->getStatusCode()) {
                                $failed = $this->customerBlacklistUpdater->processArrayData($uploadResult);
                                $this->webServiceClient->uploadCustomerBlacklistUpdateReturnFile($failed);
                            }
                        } else {
                            $io->text('No file found.');
                        }

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_PROCESS_CREDITS_WITHDRAWAL_RETURN:
                    try {
                        $date = new \DateTime($task['data']['date'], $this->timezone);
                        $returnTypes = [
                            'existing_customer_refund_return' => 'EXISTING_CUSTOMER_REFUND_RETURN',
                            'nonexisting_customer_refund_return' => 'NONEXISTING_CUSTOMER_REFUND_RETURN',
                        ];

                        foreach ($returnTypes as $path => $returnType) {
                            $io->text(\sprintf('Starting %s...', $returnType));
                            $tempFile = $this->webServiceClient->downloadXML($date, $returnType);

                            if (true === \is_resource($tempFile) && \filesize(\stream_get_meta_data($tempFile)['uri']) > 0) {
                                $io->text('Uploading to document converter...');

                                $baseDocumentConverterUri = HttpUri::createFromString($this->documentConverterHost);
                                $modifier = new AppendSegment(\sprintf('credits_transactions/xml/%s', $path));
                                $documentConverterUri = $modifier->process($baseDocumentConverterUri);

                                $multipartContent = [
                                    'headers' => [
                                        'User-Agent' => 'U-Centric API',
                                    ],
                                    'multipart' => [
                                        [
                                            'name' => 'file',
                                            'filename' => \uniqid().'.xml',
                                            'contents' => $tempFile,
                                        ],
                                    ],
                                ];
                                $uploadResponse = $this->client->request('POST', $documentConverterUri, $multipartContent);
                                $uploadResult = \json_decode((string) $uploadResponse->getBody(), true);

                                if (200 === $uploadResponse->getStatusCode()) {
                                    $this->creditsWithdrawalStatusUpdater->processArrayData($uploadResult);
                                }
                            } else {
                                $io->text('No file found.');
                            }
                        }

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_PROCESS_EVENT_ACTIVITY:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-event-activity-xml --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-event-activity-xml --env=worker';
                        }

                        $process = Process::fromShellCommandline($command);
                        $process->setTimeout(1800);
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
                case JobType::CRON_PROCESS_MASS_ACCOUNT_CLOSURE:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-mass-account-closure-xml --upload --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-mass-account-closure-xml --upload --env=worker';
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
                case JobType::CRON_PROCESS_MASS_CONTRACT_APPLICATION:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-mass-contract-application-xml --upload --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-mass-contract-application-xml --upload --env=worker';
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
                case JobType::CRON_PROCESS_MASS_CONTRACT_RENEWAL_APPLICATION:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-mass-contract-application-renewal-xml --upload --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-mass-contract-application-renewal-xml --upload --env=worker';
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
                case JobType::CRON_PROCESS_MASS_TRANSFER_OUT:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-mass-transfer-out-application-xml --upload --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-mass-transfer-out-application-xml --upload --env=worker';
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
                case JobType::CRON_PROCESS_RCCS_TERMINATION:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-rccs-termination-xml --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-mass-contract-application-xml --env=worker';
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
                case JobType::CRON_PROCESS_TARIFF_RATE_RECONCILIATION:
                    try {
                        $date = new \DateTime($task['data']['date'], $this->timezone);
                        $tempFile = $this->webServiceClient->getPromotionCodeXMLFile($date);

                        if (true === \is_resource($tempFile) && \filesize(\stream_get_meta_data($tempFile)['uri']) > 0) {
                            $io->text('Uploading to document converter...');

                            $baseDocumentConverterUri = HttpUri::createFromString($this->documentConverterHost);
                            $modifier = new AppendSegment('tariff_rates/xml');
                            $documentConverterUri = $modifier->process($baseDocumentConverterUri);

                            $multipartContent = [
                                'headers' => [
                                    'User-Agent' => 'U-Centric API',
                                ],
                                'multipart' => [
                                    [
                                        'name' => 'file',
                                        'filename' => \uniqid().'.xml',
                                        'contents' => $tempFile,
                                    ],
                                ],
                            ];
                            $uploadResponse = $this->client->request('POST', $documentConverterUri, $multipartContent);
                            $uploadResult = \json_decode((string) $uploadResponse->getBody(), true);

                            if (200 === $uploadResponse->getStatusCode()) {
                                $this->tariffRateUpdater->processArrayData($uploadResult);
                            }
                        } else {
                            $io->text('No file found.');
                        }

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_PROCESS_TRANSFER_OUT_APPLICATION:
                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:web-service:process-transfer-out-application-xml --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:web-service:process-transfer-out-application-xml --env=worker';
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
                case JobType::CRON_SCHEDULE_UPDATE_MAINTENANCE_STATUS:
                    try {
                        $startTime = new \DateTime($task['data']['date'], $this->timezone);
                        $startTime->setTime(0, 0, 0);
                        $endTime = new \DateTime($task['data']['date'], $this->timezone);
                        $endTime->setTime(23, 59, 59);

                        $qb = $this->entityManager->getRepository(MaintenanceConfiguration::class)->createQueryBuilder('mc');
                        $expr = $qb->expr();

                        $startingMaintenanceConfigurations = $qb->select('mc')
                            ->where($expr->gte('mc.plannedStartDate', $expr->literal($startTime->format('c'))))
                            ->andWhere($expr->lte('mc.plannedStartDate', $expr->literal($endTime->format('c'))))
                            ->getQuery()->getResult();

                        $endingMaintenanceConfigurations = $qb->select('mc')
                            ->where($expr->gte('mc.plannedEndDate', $expr->literal($startTime->format('c'))))
                            ->andWhere($expr->lte('mc.plannedEndDate', $expr->literal($endTime->format('c'))))
                            ->getQuery()->getResult();

                        if (\count($startingMaintenanceConfigurations) > 0) {
                            foreach ($startingMaintenanceConfigurations as $startingMaintenanceConfiguration) {
                                $maintenanceConfigurationStatusUpdaterSchedule = new DisqueJob([
                                    'data' => [
                                        'id' => $startingMaintenanceConfiguration->getId(),
                                        'type' => 'start',
                                    ],
                                    'type' => JobType::CRON_UPDATE_MAINTENANCE_STATUS,
                                ]);

                                $scheduleTime = new \DateTime();
                                $hour = (int) $startingMaintenanceConfiguration->getPlannedStartDate()->format('h');
                                $min = (int) $startingMaintenanceConfiguration->getPlannedStartDate()->format('i');
                                $sec = (int) $startingMaintenanceConfiguration->getPlannedStartDate()->format('s');

                                $scheduleTime->setTime($hour, $min, $sec);

                                if ($scheduleTime <= new \DateTime()) {
                                    $this->cronQueue->push($maintenanceConfigurationStatusUpdaterSchedule);
                                    $action = 'Pushed';
                                    $logDate = (new \DateTime())->format('r');
                                } else {
                                    $this->cronQueue->schedule($maintenanceConfigurationStatusUpdaterSchedule, $scheduleTime);
                                    $action = 'Scheduled';
                                    $logDate = $scheduleTime->format('r');
                                }
                                $io->text(\sprintf('%s %s for %s at %s', $action, JobType::CRON_UPDATE_MAINTENANCE_STATUS, $startingMaintenanceConfiguration->getId(), $logDate));
                                $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::CRON_UPDATE_MAINTENANCE_STATUS, $startingMaintenanceConfiguration->getId(), $logDate));
                            }
                        }

                        if (\count($endingMaintenanceConfigurations) > 0) {
                            foreach ($endingMaintenanceConfigurations as $endingMaintenanceConfiguration) {
                                $maintenanceConfigurationStatusUpdaterSchedule = new DisqueJob([
                                    'data' => [
                                        'id' => $endingMaintenanceConfiguration->getId(),
                                        'type' => 'end',
                                    ],
                                    'type' => JobType::CRON_UPDATE_MAINTENANCE_STATUS,
                                ]);

                                $scheduleTime = new \DateTime();
                                $hour = (int) $endingMaintenanceConfiguration->getPlannedEndDate()->format('h');
                                $min = (int) $endingMaintenanceConfiguration->getPlannedEndDate()->format('i');
                                $sec = (int) $endingMaintenanceConfiguration->getPlannedEndDate()->format('s');

                                $scheduleTime->setTime($hour, $min, $sec);

                                if ($scheduleTime <= new \DateTime()) {
                                    $this->cronQueue->push($maintenanceConfigurationStatusUpdaterSchedule);
                                    $action = 'Pushed';
                                    $logDate = (new \DateTime())->format('r');
                                } else {
                                    $this->cronQueue->schedule($maintenanceConfigurationStatusUpdaterSchedule, $scheduleTime);
                                    $action = 'Scheduled';
                                    $logDate = $scheduleTime->format('r');
                                }
                                $io->text(\sprintf('%s %s for %s at %s', $action, JobType::CRON_UPDATE_MAINTENANCE_STATUS, $endingMaintenanceConfiguration->getId(), $logDate));
                                $this->logger->info(\sprintf('%s %s for %s at %s', $action, JobType::CRON_UPDATE_MAINTENANCE_STATUS, $endingMaintenanceConfiguration->getId(), $logDate));
                            }
                        }
                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_UPLOAD_CONTRACT_APPLICATION_RECONCILIATION:
                    try {
                        $date = new \DateTime($task['data']['date'], $this->timezone);

                        $returnLog = $this->webServiceClient->uploadXML($date, UploadFileType::CONTRACT_APPLICATION);
                        if (null !== $returnLog) {
                            $io->text($returnLog);
                        }

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_UPDATE_MAINTENANCE_STATUS:
                    try {
                        $maintenanceConfigurationId = $task['data']['id'];
                        $updateType = $task['data']['type'];

                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:maintenance-configuration:update-status --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:maintenance-configuration:update-status --env=worker';
                        }

                        if (null !== $maintenanceConfigurationId) {
                            $maintenanceConfiguration = $this->entityManager->getRepository(MaintenanceConfiguration::class)->findOneBy(['id' => $maintenanceConfigurationId]);
                            if (null !== $maintenanceConfiguration) {
                                if (MaintenanceConfigurationStatus::CANCELLED === $maintenanceConfiguration->getStatus()->getValue()) {
                                    throw new \Exception('The maintenance has been cancelled already.');
                                }
                            }
                            $command .= " --maintenanceId={$maintenanceConfigurationId}";
                        }

                        if (null !== $updateType) {
                            $command .= " --type={$updateType}";
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
                case JobType::CRON_UPLOAD_CONTRACT_APPLICATION_RECONCILIATION_LEFTOVER:
                    try {
                        $date = new \DateTime($task['data']['date'], $this->timezone);
                        //leftovers are uploaded next day so
                        $date->modify('-1 day');

                        $returnLog = $this->webServiceClient->uploadXML($date, UploadFileType::CONTRACT_APPLICATION_CUTOFF_LEFTOVER);
                        if (null !== $returnLog) {
                            $io->text($returnLog);
                        }

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::CRON_UPLOAD_RCCS_TERMINATION_APPLICATION_RECONCILIATION:
                    try {
                        $date = new \DateTime($task['data']['date'], $this->timezone);

                        $returnLog = $this->webServiceClient->uploadXML($date, UploadFileType::RCCS_TERMINATION);
                        if (null !== $returnLog) {
                            $io->text($returnLog);
                        }

                        //log end of job
                        $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    } catch (\Exception $ex) {
                        $error = true;
                        $errorMessageLog = $ex->getMessage();
                    }
                    break;
                case JobType::MOTHER_OF_JOBS:
                    //run the function
                    $jobDate = new \DateTime($task['data']['date'], $this->timezone);

                    if (isset($task['data']['only-future-jobs'])) {
                        $queueFutureJobsOnly = (bool) $task['data']['only-future-jobs'];
                    }

                    try {
                        if ('' !== $this->memoryLimit) {
                            $command = \sprintf('php -d memory_limit=%s bin/console app:cron:queue-cron-job-schedule --queue=cron --env=worker', $this->memoryLimit);
                        } else {
                            $command = 'php bin/console app:cron:queue-cron-job-schedule --queue=cron --env=worker';
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

                    //schedules for billing webservice
                    // spawn next day cron job
                    $nextDate = clone $jobDate;
                    $nextDate->modify('+1 day');
                    $nextDate->setTime(0, 0, 0);

                    $nextDayJob = new DisqueJob([
                        'data' => [
                            'date' => $nextDate->format('Y-m-d H:i:s'),
                        ],
                        'type' => JobType::MOTHER_OF_JOBS,
                    ]);

                    //convert to utc for checking
                    $nextDate->setTimezone($utcTimezone);
                    if ($nextDate <= new \DateTime()) {
                        if (true === $queueFutureJobsOnly) {
                            $action = 'Skipped';
                        } else {
                            $this->cronQueue->push($nextDayJob);
                            $action = 'Pushed';
                        }
                        $logDate = (new \DateTime())->format('r');
                    } else {
                        $jobTTL = 24.5 * 60 * 60;
                        $this->cronQueue->schedule($nextDayJob, $nextDate, ['ttl' => (int) $jobTTL]);
                        $action = 'Scheduled';
                        $logDate = $nextDate->format('r');
                    }
                    $io->text(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_JOBS, $logDate));
                    $this->logger->info(\sprintf('%s %s at %s', $action, JobType::MOTHER_OF_JOBS, $logDate));
                    // spawn next day cron job

                    //log end of job
                    $endMessageLog = \sprintf('[%s] Done JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
                default:
                    $error = true;
                    $errorMessageLog = \sprintf('[%s] Wrong Queue? Fail JOB %s.', (new \DateTime())->format('r'), $job->getId());
                    break;
            }

            if (true === $error) {
                // nack the job
                $this->cronQueue->failed($job);
                $io->error($errorMessageLog);
                $this->logger->error($errorMessageLog);
            } else {
                // ack the job
                $this->cronQueue->processed($job);
                $io->text($endMessageLog);
                $this->logger->info($endMessageLog);
            }

            $tempFile = null;
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
