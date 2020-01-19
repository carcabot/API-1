<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\ApplicationRequestApi;
use App\Document\Contract;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateApplicationRequestAddresses extends Command
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ApplicationRequestApi
     */
    private $applicationRequestApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param ApplicationRequestApi  $applicationRequestApi
     * @param LoggerInterface        $logger
     */
    public function __construct(DocumentManager $documentManager, EntityManagerInterface $entityManager, ApplicationRequestApi $applicationRequestApi, LoggerInterface $logger)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->applicationRequestApi = $applicationRequestApi;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-application-request-addresses')
            ->setDescription('Migrate application request addresses by applicationId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The application request id to be migrated', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $id = (string) $input->getOption('id');

        if (!empty($id)) {
            $qb = $this->documentManager->createAggregationBuilder(Contract::class);
            $applicationRequest = $qb->hydrate(Contract::class)
                ->match()
                ->field('_applicationId')
                ->equals($id)
                ->project()
                ->includeFields(['_applicationId', 'address'])
                ->execute();

            if (\iter\count($applicationRequest) > 0) {
                $progressBar = new ProgressBar($output);
                $this->applicationRequestApi->updateApplicationRequestAddresses($applicationRequest);
                foreach ($applicationRequest as $applicationData) {
                    $progressBar->advance();
                    $io->success('Migrated all details of the application request addresses '.$applicationData->getApplicationRequestNumber());
                    $progressBar->finish();
                }
            } else {
                $io->error('Application request not found');
            }
        } elseif (empty($id)) {
            $qb = $this->documentManager->createAggregationBuilder(Contract::class);
            $applicationRequestDocument = $qb->hydrate(Contract::class)
                ->project()
                ->includeFields(['_applicationId', 'address'])
                ->execute();
            if (\count($applicationRequestDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all application request addresses ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating application request addresses..... ');
                $this->applicationRequestApi->updateApplicationRequestAddresses($applicationRequestDocument);
                $progressBar->advance();
                $io->success('Migrated all application request addresses .');
                $progressBar->finish();
            } else {
                $io->error('Application requests not found');
            }
        }

        return 0;
    }
}
