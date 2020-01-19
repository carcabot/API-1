<?php

declare(strict_types=1);

namespace App\Command;

use App\Disque\JobType;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueCronDisqueJob extends Command
{
    /**
     * @var DisqueQueue
     */
    private $applicationRequestQueue;

    /**
     * @var DisqueQueue
     */
    private $campaignQueue;

    /**
     * @var DisqueQueue
     */
    private $contractQueue;

    /**
     * @var DisqueQueue
     */
    private $cronQueue;

    /**
     * @var DisqueQueue
     */
    private $messageQueue;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param DisqueQueue $applicationRequestQueue
     * @param DisqueQueue $campaignQueue
     * @param DisqueQueue $contractQueue
     * @param DisqueQueue $cronQueue
     * @param DisqueQueue $messageQueue
     * @param string      $timezone
     */
    public function __construct(DisqueQueue $applicationRequestQueue, DisqueQueue $campaignQueue, DisqueQueue $contractQueue, DisqueQueue $cronQueue, DisqueQueue $messageQueue, string $timezone)
    {
        parent::__construct();

        $this->applicationRequestQueue = $applicationRequestQueue;
        $this->campaignQueue = $campaignQueue;
        $this->contractQueue = $contractQueue;
        $this->cronQueue = $cronQueue;
        $this->messageQueue = $messageQueue;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:cron:queue-job')
            ->setDescription('Queues the cron job.')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type of cron job', null)
            ->addOption('only-future-jobs', null, InputOption::VALUE_NONE, 'Ignore jobs that are supposed to be queued in the past.')
            ->setHelp(<<<'EOF'
The %command.name% command queues the specified cron job.
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
        $type = $input->getOption('type');

        $jobType = JobType::MOTHER_OF_JOBS;
        $queue = $this->cronQueue;

        if (null !== $type) {
            if ('campaign' === $type) {
                $jobType = JobType::MOTHER_OF_CAMPAIGN;
                $queue = $this->campaignQueue;
            } elseif ('application_request' === $type) {
                $jobType = JobType::MOTHER_OF_APPLICATION_REQUEST;
                $queue = $this->applicationRequestQueue;
            } elseif ('contract' === $type) {
                $jobType = JobType::MOTHER_OF_CONTRACT;
                $queue = $this->contractQueue;
            } elseif ('message' === $type) {
                $jobType = JobType::MOTHER_OF_MESSAGE;
                $queue = $this->messageQueue;
            } else {
                $io->error('Unknown type.');
            }
        }

        $io->comment(\sprintf('Queueing %s ...', $jobType));

        $now = new \DateTime();
        $now->setTimezone($this->timezone);
        $cronJob = new DisqueJob([
            'data' => [
                'date' => $now->format('Y-m-d H:i:s'),
                'only-future-jobs' => (bool) $input->getOption('only-future-jobs'),
            ],
            'type' => $jobType,
        ]);

        $queue->push($cronJob);

        return 0;
    }
}
