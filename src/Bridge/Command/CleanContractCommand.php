<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Disque\JobType;
use App\Entity\Contract;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanContractCommand extends Command
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
            ->setName('app:contract:clean')
            ->addOption('field', null, InputOption::VALUE_REQUIRED, 'Which field to do cleanup.')
            ->addOption('noqueue', null, InputOption::VALUE_NONE, 'Dont queue the job to run.')
            ->setDescription('Cleaning contracts.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $io->text('Checking contracts..... ');
        $count = 0;
        $contracts = [];
        $ids = [];
        $existing = [];
        $table = [];
        $field = $input->getOption('field');

        $qb = $this->entityManager->getRepository(Contract::class)->createQueryBuilder('contract');

        if ('addresses' === $field) {
            $contracts = $qb->join('contract.addresses', 'address')
                ->groupBy('contract.id')
                ->having($qb->expr()->gt('count(address)', 2))
                ->getQuery()
                ->getResult();
        } elseif ('billSubscriptionTypes' === $field) {
            $contracts = $qb->groupBy('contract.id')
                ->addGroupBy('contract.billSubscriptionTypes')
                ->having($qb->expr()->gt('jsonb_array_length(contract.billSubscriptionTypes)', 1))
                ->getQuery()
                ->getResult();
        }

        foreach ($contracts as $contract) {
            ++$count;
            $existing[] = $contract->getId();

            $table[] = [
                $contract->getId(),
                $contract->getContractNumber(),
            ];

            if (false === $input->getOption('noqueue')) {
                $this->migrationQueue->push(new DisqueJob([
                    'data' => [
                        'id' => $contract->getId(),
                        'type' => $field,
                        'count' => $count,
                    ],
                    'type' => JobType::CLEAN_CONTRACT_DATA,
                ]));
            }
        }

        $this->entityManager->clear();

        $io->table(['ID', 'Contract Number'], $table);
        if (false === $input->getOption('noqueue')) {
            $io->success(\sprintf('Queued %s contracts\' cleanup jobs.', $count));
        } else {
            $io->success(\sprintf('Found %s contracts\' cleanup jobs.', $count));
        }

        return 0;
    }
}
