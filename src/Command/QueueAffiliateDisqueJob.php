<?php

declare(strict_types=1);

namespace App\Command;

use App\Disque\JobType;
use App\WebService\Affiliate\ClientFactory as AffiliateClientFactory;
use App\WebService\Affiliate\DummyClient;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class QueueAffiliateDisqueJob extends Command
{
    /**
     * @var AffiliateClientFactory
     */
    private $affiliateClientFactory;

    /**
     * @var DisqueQueue
     */
    private $webServicesQueue;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param AffiliateClientFactory $affiliateClientFactory
     * @param DisqueQueue            $webServicesQueue
     * @param string                 $timezone
     */
    public function __construct(AffiliateClientFactory $affiliateClientFactory, DisqueQueue $webServicesQueue, string $timezone)
    {
        parent::__construct();

        $this->affiliateClientFactory = $affiliateClientFactory;
        $this->webServicesQueue = $webServicesQueue;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:queue-affiliate-fetch')
            ->setDescription('Queues affiliate fetch job.')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'Queue affiliate fetch job for a specified provider.', null)
            ->addOption('datetime', null, InputOption::VALUE_OPTIONAL, 'Schedule the job.', null)
            ->setHelp(<<<'EOF'
The %command.name% command queues transaction fetch job for affiliate provider.
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

        $provider = (string) $input->getOption('provider');
        $datetime = $input->getOption('datetime');

        $affiliateClient = $this->affiliateClientFactory->getClient($provider);

        if (!$affiliateClient instanceof DummyClient) {
            $io->success(\sprintf('Affiliate Client class for %s, %s found.', $provider, \get_class($affiliateClient)));
            $io->comment('Queueing fetch job...');

            $newJob = new DisqueJob([
                'data' => [
                    'provider' => $provider,
                ],
                'type' => JobType::AFFILIATE_PROGRAM_QUEUE_FETCH_TRANSACTION,
            ]);

            if (null !== $datetime) {
                // schedule the datetime in specified timezone from .env
                $scheduledTime = new \DateTime($datetime, $this->timezone);

                // convert back to default UTC
                $scheduledTime->setTimezone(new \DateTimeZone('UTC'));
                $this->webServicesQueue->schedule($newJob, $scheduledTime);
            } else {
                $this->webServicesQueue->push($newJob);
            }
        }

        return 0;
    }
}
