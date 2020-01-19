<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 7/2/19
 * Time: 12:04 PM.
 */

namespace App\Bridge\Command;

use App\Bridge\Services\TicketApi;
use App\Document\OldTicket;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateTicket extends Command
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
            ->setName('app:bridge:migrate-ticket')
            ->setDescription('Migrate ticket details by TicketId')
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

                $this->ticketApi->createTicket([$ticket]);
                $this->logger->info(\sprintf('Migrated ticket %s ...', $ticket->getTicketId()));

                $progressBar->advance();
                $io->success('Migrated all details of the ticket '.$ticket->getTicketId());
                $progressBar->finish();
            } else {
                $io->error('Ticket not found');
            }
        } elseif (empty($id)) {
            $qb = $this->documentManager->createQueryBuilder(OldTicket::class);
            $expr = $qb->expr();

            $ticketDocuments = $qb->addOr($expr->field('status')->equals('COMPLETED'))
                ->addOr($qb->expr()->field('status')->equals('CANCELLED'))
                ->getQuery()
                ->execute();

            if (\count($ticketDocuments) > 0) {
                $this->logger->info(\sprintf('Migrating all tickets...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating tickets ..... ');
                $progressBar->advance();
                $this->ticketApi->createTicket($ticketDocuments);
                $io->success('Migrated all tickets .');
                $progressBar->finish();
            } else {
                $io->error('Ticket not found');
            }
        }

        return 0;
    }
}
