<?php

declare(strict_types=1);

namespace App\Command;

use App\Disque\JobType;
use App\Entity\ApplicationRequest;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateReconciliationFile extends Command
{
    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param DisqueQueue            $emailsQueue
     * @param EntityManagerInterface $entityManager
     * @param WebServiceClient       $webServiceClient
     * @param LoggerInterface        $logger
     * @param string                 $timezone
     */
    public function __construct(DisqueQueue $emailsQueue, EntityManagerInterface $entityManager, WebServiceClient $webServiceClient, LoggerInterface $logger, string $timezone)
    {
        parent::__construct();

        $this->emailsQueue = $emailsQueue;
        $this->entityManager = $entityManager;
        $this->webServiceClient = $webServiceClient;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:generate-reconciliation-file')
            ->setDescription('Generates a reconciliation xml file.')
            ->addOption('end-date', null, InputOption::VALUE_OPTIONAL, 'To which specific end date (YYYY-MM-DD)', null)
            ->addOption('start-date', null, InputOption::VALUE_OPTIONAL, 'From which specific start date (YYYY-MM-DD)', null)
            ->addOption('notify', null, InputOption::VALUE_NONE, 'Indicator for sending email notification')
            ->setHelp(<<<'EOF'
The %command.name% command generates a contract application reconciliation xml file.
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

        $endDateString = $input->getOption('end-date');
        $startDateString = $input->getOption('start-date');

        $startDate = new \DateTime('now', $this->timezone);
        if (null !== $startDateString) {
            $startDate = new \DateTime($startDateString, $this->timezone);
        }

        $utcTimezone = new \DateTimeZone('UTC');
        $startOfDay = clone $startDate;
        $startOfDay->setTime(0, 0, 0)->setTimezone($utcTimezone);

        $endDate = new \DateTime('now', $this->timezone);
        if (null !== $endDateString) {
            $endDate = new \DateTime($endDateString, $this->timezone);
        }

        $endDate->setTime(23, 59, 59);
        $endOfDay = clone $endDate;
        $endOfDay->setTimezone($utcTimezone);

        $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('applicationRequest');
        $expr = $qb->expr();

        $applicationRequests = $qb->where($expr->eq('applicationRequest.status', ':status'))
            ->andWhere($expr->gte('applicationRequest.dateSubmitted', ':startDate'))
            ->andWhere($expr->lte('applicationRequest.dateSubmitted', ':endDate'))
            ->setParameters([
                'endDate' => $endOfDay->format('c'),
                'startDate' => $startOfDay->format('c'),
                'status' => ApplicationRequestStatus::IN_PROGRESS,
            ])
            ->getQuery()
            ->getResult();

        if ($startDate->format('Ymd') === $endDate->format('Ymd')) {
            $filenameSuffix = 'RECON-'.$startDate->format('Ymd');
        } else {
            $filenameSuffix = 'RECON-'.$startDate->format('Ymd').'-'.$endDate->format('Ymd');
        }

        $failedApplicationGroups = [];
        if (\count($applicationRequests) > 0) {
            $progressBar = new ProgressBar($output, \count($applicationRequests));

            foreach ($applicationRequests as $applicationRequest) {
                $progressBar->advance();
                $statusHistory = $this->webServiceClient->getApplicationRequestStatusHistory($applicationRequest);

                if (empty($statusHistory)) {
                    if (ApplicationRequestType::ACCOUNT_CLOSURE === $applicationRequest->getType()->getValue()) {
                        $filenamePrefix = 'CRM_FRCClosure_';
                    } elseif (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
                        $filenamePrefix = 'CRM_FRCAPP_';
                    } elseif (ApplicationRequestType::GIRO_TERMINATION === $applicationRequest->getType()->getValue()) {
                        $filenamePrefix = 'CRM_GIRO_Termination_Request_';
                    } elseif (ApplicationRequestType::TRANSFER_OUT === $applicationRequest->getType()->getValue()) {
                        $filenamePrefix = 'CRM_FRCTO_';
                    } elseif (ApplicationRequestType::RCCS_TERMINATION === $applicationRequest->getType()->getValue()) {
                        $filenamePrefix = 'CRM_RCCS_Termination_Request_';
                    } else {
                        $filenamePrefix = '';
                    }

                    $key = $applicationRequest->getType()->getValue().' - '.$filenamePrefix.$filenameSuffix.'.xml';
                    $failedApplicationGroups[$key][] = [
                        'ID' => $applicationRequest->getId(),
                        'Application Number' => $applicationRequest->getApplicationRequestNumber(),
                    ];

                    $this->webServiceClient->submitApplicationRequest($applicationRequest, $filenameSuffix, true);
                }
            }
            $progressBar->finish();
            $io->newLine();
        }

        foreach ($failedApplicationGroups as $filename => $failedApplicationGroup) {
            $table = new Table($output);
            $table->setHeaderTitle($filename);
            $table->setHeaders(['ID', 'Application Number']);
            $table->setRows($failedApplicationGroup);
            $table->render();
            $io->newLine();
        }

        if (true === $input->getOption('notify') && 0 !== \count($failedApplicationGroups)) {
            $this->emailsQueue->push(new DisqueJob([
                'data' => [
                    'subject' => 'No Status History!',
                    'tableData' => $failedApplicationGroups,
                ],
                'type' => JobType::ADMIN_NOTIFICATION,
            ]));
        }

        $io->text('Done.');

        return 0;
    }
}
