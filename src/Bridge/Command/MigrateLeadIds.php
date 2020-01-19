<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 20/2/19
 * Time: 6:32 PM.
 */

namespace App\Bridge\Command;

use App\Bridge\Services\LeadApi;
use App\Bridge\Services\OldLeadApi;
use App\Document\OldLeadIds;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateLeadIds extends Command
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
     * @var OldLeadApi
     */
    private $leadApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DocumentManager        $documentManager
     * @param LoggerInterface        $logger
     * @param OldLeadApi             $leadApi
     */
    public function __construct(EntityManagerInterface $entityManager, DocumentManager $documentManager, LoggerInterface $logger, OldLeadApi $leadApi)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->leadApi = $leadApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-lead-ids')
            ->setDescription('Migrate lead ids by the Id')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The lead id to be migrated', null)
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
            $lead = $this->documentManager->getRepository(OldLeadIds::class)->findOneBy(['id' => $id]);

            if (null !== $lead) {
                $progressBar = new ProgressBar($output);

                $this->leadApi->createIds($lead);
                $this->logger->info(\sprintf('Migrated lead id %s ...', $lead->getId()));

                $progressBar->advance();
                $io->success('Migrated all details of the lead ids'.$lead->getId());
                $progressBar->finish();
            } else {
                $io->error('Lead id not found');
            }
        } elseif (empty($id)) {
            $leadDocument = $this->documentManager->getRepository(OldLeadIds::class)->findAll();

            if (\count($leadDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all lead ids...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating lead ids..... ');
                $progressBar->advance();

                foreach ($leadDocument as $lead) {
                    $this->leadApi->createIds($lead);
                }
                $io->success('Migrated all lead ids.');

                $progressBar->finish();
            } else {
                $io->error('Lead id not found');
            }
        }

        return 0;
    }
}
