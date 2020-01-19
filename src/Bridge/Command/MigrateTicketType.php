<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 7/2/19
 * Time: 4:10 PM.
 */

namespace App\Bridge\Command;

use App\Bridge\Services\TicketApi;
use App\Document\OldTicketType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateTicketType extends Command
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
            ->setName('app:bridge:migrate-ticket-type')
            ->setDescription('Migrate ticket type details by ticketId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The ticket type id to be migrated', null)
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
            $ticket = $this->documentManager->getRepository(OldTicketType::class)->findOneBy(['id' => $id]);

            if (null !== $ticket) {
                $progressBar = new ProgressBar($output);

                $this->ticketApi->createTicketType([$ticket]);
                $this->logger->info(\sprintf('Migrated ticket type %s ...', $ticket->getName()));

                $progressBar->advance();
                $io->success('Migrated all details of the ticket type'.$ticket->getName());
                $progressBar->finish();
            } else {
                $io->error('Ticket type not found');
            }
        } elseif (empty($id)) {
            $ticketDocument = $this->documentManager->getRepository(OldTicketType::class)->findAll();

            if (\count($ticketDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all tickets type...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating tickets types..... ');
                $progressBar->advance();

                $this->ticketApi->createTicketType($ticketDocument);
                $io->success('Migrated all ticket types.');

                $progressBar->finish();
            } else {
                $io->error('Ticket type not found');
            }
        }

        return 0;
    }
}
