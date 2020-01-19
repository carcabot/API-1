<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 7/2/19
 * Time: 12:12 PM.
 */

namespace App\Bridge\Command;

use App\Bridge\Services\TicketApi;
use App\Document\OldTicketCategories;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateTicketCategory extends Command
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
     * @var TicketApi
     */
    private $ticketApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DocumentManager        $documentManager
     * @param LoggerInterface        $logger
     * @param TicketApi              $ticketApi
     */
    public function __construct(EntityManagerInterface $entityManager, DocumentManager $documentManager, LoggerInterface $logger, TicketApi $ticketApi)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->ticketApi = $ticketApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-ticket-category')
            ->setDescription('Migrate ticket category details by ticketId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The ticket category id to be migrated', null)
            ->addOption('category', null, null, 'To migrate the main and sub categories of ticket category')
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
            $ticket = $this->documentManager->getRepository(OldTicketCategories::class)->findOneBy(['id' => $id]);

            if (null !== $ticket) {
                $progressBar = new ProgressBar($output);

                $this->ticketApi->createTicketCategory([$ticket]);
                $this->logger->info(\sprintf('Migrated ticket category %s ...', $ticket->getId()));

                $progressBar->advance();
                $io->success('Migrated all details of the ticket category'.$ticket->getId());
                $progressBar->finish();
            } else {
                $io->error('Ticket category not found');
            }
        } elseif (empty($id)) {
            $ticketDocument = $this->documentManager->getRepository(OldTicketCategories::class)->findAll();

            if (\count($ticketDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all tickets categories...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating tickets categories..... ');
                $progressBar->advance();

                if (false === $input->getOption('category')) {
                    $this->ticketApi->createTicketCategory($ticketDocument);
                    $io->success('Migrated all tickets categories.');
                } else {
                    $this->ticketApi->updateTicketCategorysCategory($ticketDocument);
                    $io->success('Migrated and updated all main and sub categories of tickets categories.');
                }
                $progressBar->finish();
            } else {
                $io->error('Ticket category not found');
            }
        }

        return 0;
    }
}
