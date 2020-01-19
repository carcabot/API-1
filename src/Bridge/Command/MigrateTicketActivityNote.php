<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 18/2/19
 * Time: 6:56 PM.
 */

namespace App\Bridge\Command;

namespace App\Bridge\Command;

use App\Bridge\Services\TicketApi;
use App\Document\OldTicket;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateTicketActivityNote extends Command
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
            ->setName('app:bridge:migrate-ticket-activity-note')
            ->setDescription('Migrate ticket activity and notes by TicketId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The ticket id to be migrated', null)
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
            $ticket = $this->documentManager->getRepository(OldTicket::class)->findOneBy(['id' => $id]);

            if (null !== $ticket) {
                $progressBar = new ProgressBar($output);

                $this->ticketApi->createTicketActivityNote([$ticket]);
                $progressBar->advance();
                $io->success('Migrated all activities and notes of the ticket '.$ticket->getTicketId());
                $progressBar->finish();
            } else {
                $io->error('Ticket not found');
            }
        } elseif (empty($id)) {
            $ticketDocument = $this->documentManager->getRepository(OldTicket::class)->findAll();

            if (\count($ticketDocument) > 0) {
                $progressBar = new ProgressBar($output);
                $io->text('Migrating tickets activities and notes ..... ');
                $progressBar->advance();
                $this->ticketApi->createTicketActivityNote($ticketDocument);
                $io->success('Migrated all tickets activity and notes.');
                $progressBar->finish();
            } else {
                $io->error('Ticket not found');
            }
        }

        return 0;
    }
}
