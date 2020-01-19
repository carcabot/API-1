<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 18/1/19
 * Time: 7:12 PM.
 */

namespace App\Bridge\Command;

use App\Bridge\Services\OldLeadApi;
use App\Document\OldLead;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateLead extends Command
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
    public function __construct(EntityManagerInterface $entityManager, DocumentManager $documentManager, OldLeadApi $leadApi, LoggerInterface $logger)
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
            ->setName('app:bridge:migrate-lead')
            ->setDescription('Migrate lead details by leadId')
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
            $lead = $this->documentManager->getRepository(OldLead::class)->findOneBy(['id' => $id]);

            if (null !== $lead) {
                $progressBar = new ProgressBar($output);

                $this->leadApi->createLead([$lead]);
                $this->logger->info(\sprintf('Migrated lead %s ...', $lead->getLeadId()));

                $progressBar->advance();
                $io->success('Migrated all details of the lead '.$lead->getLeadId());
                $progressBar->finish();
            } else {
                $io->error('Lead not found');
            }
        } elseif (empty($id)) {
            $leadDocument = $this->documentManager->getRepository(OldLead::class)->findAll();

            if (\count($leadDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all leads ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating leads ..... ');
                $progressBar->advance();
                $this->leadApi->createLead($leadDocument);
                $io->success('Migrated all leads .');
                $progressBar->finish();
            } else {
                $io->error('Leads not found');
            }
        }

        return 0;
    }
}
