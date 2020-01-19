<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Ticket;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateTask extends Command
{
    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param WebServiceClient       $webServiceClient
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(WebServiceClient $webServiceClient, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();

        $this->webServiceClient = $webServiceClient;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:create-task')
            ->setDescription('Creates a task.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Create which task (id)', null)
            ->addOption('fail', null, InputOption::VALUE_NONE, 'Force fail indicator')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $input->getOption('id');
        $fail = $input->getOption('fail');

        $ticket = $this->entityManager->getRepository(Ticket::class)->find($id);

        if (null === $ticket) {
            $ticket = $this->entityManager->getRepository(Ticket::class)->findOneBy(['tickerNumber' => $id]);
        }

        if (null !== $ticket) {
            $io->success(\sprintf('Ticket #%s found.', $id));
            $io->comment('Creating web service.');

            $result = $this->webServiceClient->createTask($ticket, $fail);

            $io->text($result);
        } else {
            $io->error('Ticket not found');
        }

        return 0;
    }
}
