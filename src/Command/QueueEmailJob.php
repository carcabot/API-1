<?php

declare(strict_types=1);

namespace App\Command;

use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueEmailJob extends Command
{
    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DisqueQueue     $emailsQueue
     * @param LoggerInterface $logger
     */
    public function __construct(DisqueQueue $emailsQueue, LoggerInterface $logger)
    {
        parent::__construct();

        $this->emailsQueue = $emailsQueue;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:email:queue')
            ->setDescription('Queues an email to be sent.')
            ->addOption('body', null, InputOption::VALUE_REQUIRED, 'JSON representation of the job body', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $body = \json_decode($input->getOption('body'), true);

        $this->emailsQueue->push(new DisqueJob($body));

        return 0;
    }
}
