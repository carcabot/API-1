<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Disque\JobType;
use App\Entity\CustomerAccount;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanCustomerCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DisqueQueue
     */
    private $migrationQueue;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DisqueQueue            $migrationQueue
     */
    public function __construct(EntityManagerInterface $entityManager, DisqueQueue $migrationQueue)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->migrationQueue = $migrationQueue;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:customer:clean')
            ->addOption('field', null, InputOption::VALUE_REQUIRED, 'Which field to do cleanup.')
            ->addOption('noqueue', null, InputOption::VALUE_NONE, 'Dont queue the job to run.')
            ->setDescription('Cleaning customers.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $io->text('Checking customer accounts..... ');
        $count = 0;
        $customers = [];
        $ids = [];
        $table = [];
        $field = $input->getOption('field');

        $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');

        if ('addresses' === $field) {
            $customers = $qb->join('customer.addresses', 'address')
                ->groupBy('customer.id')
                ->having($qb->expr()->gt('count(address)', 1))
                ->getQuery()
                ->getResult();
        }

        foreach ($customers as $customer) {
            ++$count;

            $table[] = [
                $customer->getId(),
                $customer->getAccountNumber(),
            ];

            if (false === $input->getOption('noqueue')) {
                $this->migrationQueue->push(new DisqueJob([
                    'data' => [
                        'id' => $customer->getId(),
                        'type' => $field,
                        'count' => $count,
                    ],
                    'type' => JobType::CLEAN_CUSTOMER_DATA,
                ]));
            }
        }

        $this->entityManager->clear();

        $io->table(['ID', 'Account Number'], $table);
        if (false === $input->getOption('noqueue')) {
            $io->success(\sprintf('Queued %s customers\' cleanup jobs.', $count));
        } else {
            $io->success(\sprintf('Found %s customers\' cleanup jobs.', $count));
        }

        return 0;
    }
}
