<?php

declare(strict_types=1);

namespace App\Command;

use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueDisqueJob extends Command
{
    /**
     * @var DisqueQueue
     */
    private $applicationRequestsQueue;

    /**
     * @var DisqueQueue
     */
    private $campaignsQueue;

    /**
     * @var DisqueQueue
     */
    private $contractsQueue;

    /**
     * @var DisqueQueue
     */
    private $cronQueue;

    /**
     * @var DisqueQueue
     */
    private $emailsQueue;

    /**
     * @var DisqueQueue
     */
    private $reportsQueue;

    /**
     * @var DisqueQueue
     */
    private $messagesQueue;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param DisqueQueue $applicationRequestsQueue
     * @param DisqueQueue $campaignsQueue
     * @param DisqueQueue $contractsQueue
     * @param DisqueQueue $cronQueue
     * @param DisqueQueue $emailsQueue
     * @param DisqueQueue $reportsQueue
     * @param DisqueQueue $messagesQueue
     * @param string      $timezone
     */
    public function __construct(DisqueQueue $applicationRequestsQueue, DisqueQueue $campaignsQueue, DisqueQueue $contractsQueue, DisqueQueue $cronQueue, DisqueQueue $emailsQueue, DisqueQueue $reportsQueue, DisqueQueue $messagesQueue, string $timezone)
    {
        parent::__construct();

        $this->applicationRequestsQueue = $applicationRequestsQueue;
        $this->campaignsQueue = $campaignsQueue;
        $this->contractsQueue = $contractsQueue;
        $this->cronQueue = $cronQueue;
        $this->emailsQueue = $emailsQueue;
        $this->reportsQueue = $reportsQueue;
        $this->messagesQueue = $messagesQueue;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:disque:queue-job')
            ->setDescription('Queues a specified job.')
            ->addOption('body', null, InputOption::VALUE_REQUIRED, 'The job body.', null)
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Which queue the job belongs to.', null)
            ->addOption('schedule-date', null, InputOption::VALUE_OPTIONAL, 'The date/time it is supposed to be queued.', null)
            ->setHelp(<<<'EOF'
The %command.name% command queues the disque job.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $body = \json_decode($input->getOption('body'), true);
        $queue = (string) $input->getOption('queue');
        $scheduledTime = $input->getOption('schedule-date');

        $queues = [
            'application_requests' => $this->applicationRequestsQueue,
            'campaigns' => $this->campaignsQueue,
            'contracts' => $this->contractsQueue,
            'cron' => $this->cronQueue,
            'emails' => $this->emailsQueue,
            'messages' => $this->messagesQueue,
            'reports' => $this->reportsQueue,
        ];

        if (!isset($queues[$queue])) {
            $tableArr = [];
            foreach ($queues as $key => $type) {
                $tableArr[] = [$key];
            }

            $io->error('Queues supported.');
            $io->table(['--queue'], $tableArr);

            return 0;
        }

        $job = new DisqueJob($body);
        $io->text(\json_encode($job->getBody(), JSON_PRETTY_PRINT));

        if (null !== $scheduledTime) {
            $scheduledTime = new \DateTime($scheduledTime, $this->timezone);
            $io->text(\sprintf('Scheduled Time: %s', $scheduledTime->format('r')));
            $scheduledTime->setTimezone(new \DateTimeZone('UTC'));

            $queues[$queue]->schedule($job, $scheduledTime);
            $io->text(\sprintf('[%s] Scheduled JOB %s.', (new \DateTime())->format('r'), $job->getId()));
        } else {
            $queues[$queue]->push($job);
            $io->text(\sprintf('[%s] Queued JOB %s.', (new \DateTime())->format('r'), $job->getId()));
        }

        return 0;
    }
}
