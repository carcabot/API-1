<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 18/2/19
 * Time: 9:45 AM.
 */

namespace App\Bridge\Command;

use App\Bridge\Services\TicketApi;
use App\Document\OldTicket;
use App\Document\OldTicketCategories;
use App\Document\OldTicketIds;
use App\Document\OldTicketType;
use App\Document\OldTicketTypeAssignment;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateTicketModule extends Command
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
     * @param EntityManagerInterface $entityManager
     * @param DocumentManager        $documentManager
     * @param TicketApi              $ticketApi
     */
    public function __construct(EntityManagerInterface $entityManager, DocumentManager $documentManager, TicketApi $ticketApi)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->ticketApi = $ticketApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-ticket-module')
            ->setDescription('Migrate all the details of ticket')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $ticketDocument = $this->documentManager->getRepository(OldTicket::class)->findAll();
        $ticketCategoryDocument = $this->documentManager->getRepository(OldTicketCategories::class)->findAll();
        $ticketTypeDocument = $this->documentManager->getRepository(OldTicketType::class)->findAll();
        $ticketTypeCategoryDocument = $this->documentManager->getRepository(OldTicketTypeAssignment::class)->findAll();
        $ticketIdDocument = $this->documentManager->getRepository(OldTicketIds::class)->findAll();

        if (\count($ticketCategoryDocument) > 0) {
            $progressBar = new ProgressBar($output);
            $io->text('Migrating tickets categories..... ');
            $progressBar->advance();
            $this->ticketApi->createTicketCategory($ticketCategoryDocument);
            $io->success('Migrated all tickets categories.');
            $progressBar->finish();
        }

        if (\count($ticketCategoryDocument) > 0) {
            $progressBar = new ProgressBar($output);
            $io->text('Migrating tickets categories..... ');
            $progressBar->advance();
            $this->ticketApi->updateTicketCategorysCategory($ticketCategoryDocument);
            $io->success('Migrated and updated all main and sub categories of tickets categories.');
            $progressBar->finish();
        }

        if (\count($ticketTypeDocument) > 0) {
            $progressBar = new ProgressBar($output);
            $io->text('Migrating tickets types..... ');
            $progressBar->advance();
            $this->ticketApi->createTicketType($ticketTypeDocument);
            $io->success('Migrated all ticket types.');
            $progressBar->finish();
        }

        if (\count($ticketTypeCategoryDocument) > 0) {
            $progressBar = new ProgressBar($output);
            $io->text('Migrating ticket type categories and ticket category types..... ');
            $progressBar->advance();
            $this->ticketApi->createTicketTypeCategory($ticketTypeCategoryDocument);
            $io->success('Migrated all ticket type categories and ticket category types.');
            $progressBar->finish();
        }

        if (\count($ticketDocument) > 0) {
            $progressBar = new ProgressBar($output);
            $io->text('Migrating tickets ..... ');
            $progressBar->advance();
            $this->ticketApi->createTicket($ticketDocument);
            $io->success('Migrated all tickets .');
            $progressBar->finish();
        }

        if (\count($ticketDocument) > 0) {
            $progressBar = new ProgressBar($output);
            $io->text('Migrating tickets activities and notes ..... ');
            $progressBar->advance();
            $this->ticketApi->createTicketActivityNote($ticketDocument);
            $io->success('Migrated all tickets activity and notes.');
            $progressBar->finish();
        }

        if (\count($ticketIdDocument) > 0) {
            $progressBar = new ProgressBar($output);
            $io->text('Migrating tickets ids..... ');
            $progressBar->advance();
            foreach ($ticketIdDocument as $ticket) {
                $this->ticketApi->createIds($ticket);
            }
            $io->success('Migrated all ticket ids.');
        }

        return 0;
    }
}
