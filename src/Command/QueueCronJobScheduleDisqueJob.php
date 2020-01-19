<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\CronJobSchedule;
use App\Enum\QueueName;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueCronJobScheduleDisqueJob extends Command
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
    private $messagesQueue;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param DisqueQueue            $applicationRequestsQueue
     * @param DisqueQueue            $campaignsQueue
     * @param DisqueQueue            $contractsQueue
     * @param DisqueQueue            $cronQueue
     * @param DisqueQueue            $messagesQueue
     * @param EntityManagerInterface $entityManager
     * @param string                 $timezone
     */
    public function __construct(DisqueQueue $applicationRequestsQueue, DisqueQueue $campaignsQueue, DisqueQueue $contractsQueue, DisqueQueue $cronQueue, DisqueQueue $messagesQueue, EntityManagerInterface $entityManager, string $timezone)
    {
        parent::__construct();

        $this->applicationRequestsQueue = $applicationRequestsQueue;
        $this->campaignsQueue = $campaignsQueue;
        $this->contractsQueue = $contractsQueue;
        $this->cronQueue = $cronQueue;
        $this->messagesQueue = $messagesQueue;
        $this->entityManager = $entityManager;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:cron:queue-cron-job-schedule')
            ->setDescription('Queues the cron job schedule for a particular queue.')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'The queue', null)
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
        $queue = (string) $input->getOption('queue');
        $queueFutureJobsOnly = (bool) $input->getOption('only-future-jobs');
        $queueName = null;

        $queues = [
            'application_requests' => $this->applicationRequestsQueue,
            'campaigns' => $this->campaignsQueue,
            'contracts' => $this->contractsQueue,
            'cron' => $this->cronQueue,
            'messages' => $this->messagesQueue,
        ];

        switch ($queue) {
            case 'application_requests':
                $queueName = new QueueName(QueueName::APPLICATION_REQUEST);
                break;
            case 'campaigns':
                $queueName = new QueueName(QueueName::CAMPAIGN);
                break;
            case 'contracts':
                $queueName = new QueueName(QueueName::CONTRACT);
                break;
            case 'cron':
                $queueName = new QueueName(QueueName::CRON);
                break;
            case 'messages':
                $queueName = new QueueName(QueueName::MESSAGE);
                break;
            default:
                $io->error('Unknown type.');

                return 0;
        }

        $io->comment(\sprintf('Processing cron job schedule for %s ...', $queue));

        //schedules for cron jobs
        $cronJobSchedules = $this->entityManager->getRepository(CronJobSchedule::class)->findBy(['queue' => $queueName]);
        $utcTimezone = new \DateTimeZone('UTC');

        foreach ($cronJobSchedules as $cronJob) {
            if (!$cronJob->isEnabled()) {
                continue;
            }

            $dateInterval = $cronJob->getIntervals();
            $jobDateTime = new \DateTime('now', $this->timezone);
            if (null !== $cronJob->getTime()) {
                $jobDateTime->setTime((int) $cronJob->getTime()->format('H'), (int) $cronJob->getTime()->format('i'), (int) $cronJob->getTime()->format('s'));
            }
            $doOffset = false;

            do {
                if ($doOffset && null !== $dateInterval) {
                    $jobDateTime->add($dateInterval);
                    if (0 === (int) $jobDateTime->format('H')) {
                        break;
                    }
                }

                $processJob = new DisqueJob([
                    'data' => [
                        'date' => $jobDateTime->format('j M Y H:i:s'),
                    ],
                    'type' => $cronJob->getJobType(),
                ]);

                //convert to utc for checking
                $utcJobDateTime = clone $jobDateTime;
                $utcJobDateTime->setTimezone($utcTimezone);
                if ($utcJobDateTime <= new \DateTime()) {
                    if (true === $queueFutureJobsOnly) {
                        $action = 'Skipped';
                    } else {
                        $queues[$queue]->push($processJob);
                        $action = 'Pushed';
                    }
                } else {
                    $queues[$queue]->schedule($processJob, $utcJobDateTime);
                    $action = 'Scheduled';
                }
                $io->text(\sprintf('%s %s at %s', $action, $cronJob->getJobType(), $jobDateTime->format('r')));
                $doOffset = true;
            } while (null !== $dateInterval);
        }

        return 0;
    }
}
