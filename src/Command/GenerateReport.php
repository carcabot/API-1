<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\DocumentType;
use App\Model\ReportGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateReport extends Command
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ReportGenerator        $reportGenerator
     * @param LoggerInterface        $logger
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, ReportGenerator $reportGenerator, LoggerInterface $logger, string $timezone)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->reportGenerator = $reportGenerator;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:generate:report')
            ->setDescription('Generates a report.')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type of report', null)
            ->addOption('start-date', null, InputOption::VALUE_OPTIONAL, 'Start date of report (YYYY-MM-DD)', null)
            ->addOption('end-date', null, InputOption::VALUE_OPTIONAL, 'End date of report (YYYY-MM-DD)', null)
            ->addOption('data', null, InputOption::VALUE_OPTIONAL, 'Parameters for processing', null)
            ->addOption('documentId', null, InputOption::VALUE_OPTIONAL, 'ID of the Internal Document to update', null)
            ->addOption('recipient', null, InputOption::VALUE_OPTIONAL, 'Email of the User to send the report to', null)
            ->setHelp(<<<'EOF'
The %command.name% command generates a report.
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

        $type = (string) $input->getOption('type');
        $startDate = $input->getOption('start-date');
        $endDate = $input->getOption('end-date');
        $params = $input->getOption('data');
        $documentId = $input->getOption('documentId');
        $recipient = $input->getOption('recipient');
        $reportParams = [];

        $reportTypes = [
            '1' => DocumentType::PARTNER_CONTRACT_APPLICATION_REQUEST_REPORT,
            '2' => DocumentType::APPLICATION_REQUEST_REPORT,
            '3' => DocumentType::CONTRACT_REPORT,
            '4' => DocumentType::CREDITS_ACTION_REPORT,
            '5' => DocumentType::CUSTOMER_ACCOUNT_RELATIONSHIP_REPORT,
            '6' => DocumentType::CUSTOMER_ACCOUNT_REPORT,
            '7' => DocumentType::LEAD_REPORT,
            '8' => DocumentType::ORDER_REPORT,
            '9' => DocumentType::TICKET_REPORT,
            '10' => DocumentType::USER_REPORT,
        ];
        $reportDocuments = [];

        if (!isset($reportTypes[$type])) {
            $tableArr = [];
            foreach ($reportTypes as $key => $type) {
                $tableArr[] = [(string) $key, $type];
            }

            $io->error('Report type what?');
            $io->table(['Code', 'Type'], $tableArr);

            return 0;
        }

        if (null === $params) {
            if (null !== $startDate) {
                $reportParams['startDate'] = $startDate;
                $startDate = new \DateTime($startDate, $this->timezone);
            } else {
                $reportParams['startDate'] = 'now';
                $startDate = new \DateTime('now', $this->timezone);
            }

            if (null !== $endDate) {
                $reportParams['endDate'] = $endDate;
                $endDate = new \DateTime($endDate, $this->timezone);
            } else {
                $reportParams['endDate'] = 'now';
                $endDate = clone $startDate;
            }

            if ($endDate < $startDate) {
                $io->error('Date what?');

                return 0;
            }

            $startDate->setTime(0, 0, 0);
            $endDate->setTime(23, 59, 59);
        } else {
            $reportParams = \unserialize($params);
        }
        $allReportData = [];
        $path = null;

        if ('1' === $type) {
            if ($startDate instanceof \DateTime && $endDate instanceof \DateTime) {
                $allReportData = $this->reportGenerator->createPartnerContractApplicationReport($startDate, $endDate);
                $allDraftInProgressVoidedReport = $this->reportGenerator->createPartnerContractApplicationReport(null, $endDate, 'draft_in_progress_voided', \sprintf('%s%s_%s.xlsx', 'Partnership Report_', 'ALL', 'DraftInProgressVoided'));
                $allReportData[] = \array_pop($allDraftInProgressVoidedReport);
            }
        } elseif ('2' === $type) {
            $allReportData = $this->reportGenerator->createApplicationRequestReport($reportParams);
        } elseif ('3' === $type) {
            $allReportData = $this->reportGenerator->createContractReport($reportParams);
        } elseif ('4' === $type) {
            $allReportData = $this->reportGenerator->createCreditsActionReport($reportParams);
        } elseif ('5' === $type) {
            $allReportData = $this->reportGenerator->createCustomerAccountRelationshipReport($reportParams);
        } elseif ('6' === $type) {
            $allReportData = $this->reportGenerator->createCustomerAccountReport($reportParams);
        } elseif ('7' === $type) {
            $allReportData = $this->reportGenerator->createLeadReport($reportParams);
        } elseif ('8' === $type) {
            $allReportData = $this->reportGenerator->createOrderReport($reportParams);
        } elseif ('9' === $type) {
            $allReportData = $this->reportGenerator->createTicketReport($reportParams);
        } elseif ('10' === $type) {
            $allReportData = $this->reportGenerator->createUserReport($reportParams);
        }

        if (!empty($allReportData)) {
            if (!empty($documentId)) {
                $allReportData['documentId'] = $documentId;
            }

            if ('1' !== $type) {
                $allReportData = [$allReportData];
            }

            $reports = $this->reportGenerator->convertDataToInternalDocument($allReportData, $reportTypes[$type]);
            if (!empty($recipient)) {
                $this->reportGenerator->queueEmailJob($reports, [$recipient]);
            }
            foreach ($reports as $report) {
                $reportDocuments[] = [(string) $report->getId(), $report->getName()];
            }

            if (!empty($reportDocuments)) {
                $io->success('Reports created successfully!');
                $io->table(['ID', 'Filename'], $reportDocuments);
            }
        } else {
            $io->error('No data found for a report.');
        }

        return 0;
    }
}
