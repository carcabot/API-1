<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 21/1/19
 * Time: 11:27 AM.
 */

namespace App\Bridge\Command;

use App\Bridge\Services\ApplicationIdsApi;
use App\Document\OldApplicationIds;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateApplicationIds extends Command
{
    /**
     * @var ApplicationIdsApi
     */
    private $applicationIdsApi;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ApplicationIdsApi      $applicationIdsApi
     * @param EntityManagerInterface $entityManager
     * @param DocumentManager        $documentManager
     * @param LoggerInterface        $logger
     */
    public function __construct(ApplicationIdsApi $applicationIdsApi, EntityManagerInterface $entityManager, DocumentManager $documentManager, LoggerInterface $logger)
    {
        parent::__construct();
        $this->applicationIdsApi = $applicationIdsApi;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-application-ids')
            ->setDescription('Migrates application ids details by id')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The application ids to be migrated', null)
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
            $application = $this->documentManager->getRepository(OldApplicationIds::class)->findOneBy(['id' => $id]);

            if (null !== $application) {
                $progressBar = new ProgressBar($output);

                $this->applicationIdsApi->createIds($application);
                $this->logger->info(\sprintf('Migrated application id %s ...', $application->getId()));

                $progressBar->advance();
                $io->success('Migrated all details of the application id '.$application->getId());
                $progressBar->finish();
            } else {
                $io->error('Application id not found');
            }
        } elseif (empty($id)) {
            $applicationDocument = $this->documentManager->getRepository(OldApplicationIds::class)->findAll();

            if (\count($applicationDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all application id...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating all application id ..... ');
                foreach ($applicationDocument as $application) {
                    $this->applicationIdsApi->createIds($application);
                    $this->logger->info(\sprintf('Migrated application %s ...', $application->getId()));
                }
                $progressBar->advance();
                $io->success('Migrated all application id .');
                $progressBar->finish();
            } else {
                $io->error('application ids not found');
            }
        }

        return 0;
    }
}
