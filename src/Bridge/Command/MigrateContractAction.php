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

class MigrateContractAction extends Command
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
            ->setName('app:bridge:migrate-contract-action')
            ->setDescription('Migrate contract action')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The contract id\'s actions to be migrated', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $id = (string) $input->getOption('id');

        $contracts = [];

        if (!empty($id)) {
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $id]);
            $io->text('Creating action for '.$id);

            if (null === $contract) {
                $io->error('Nothing found for '.$id);

                return 0;
            }

            $contracts[] = $contract;
        } else {
            $io->text('Creating action for all');
            $contracts = $this->entityManager->getRepository(Contract::class)->findAll();
        }

        $count = 0;
        $total = \count($contracts);
        foreach ($contracts as $contract) {
            ++$count;
            $this->migrationQueue->push(new DisqueJob([
                'data' => [
                    'id' => $contract->getId(),
                    'count' => $count,
                    'maxCount' => $total,
                ],
                'type' => JobType::MIGRATE_CONTRACT_ACTION,
            ]));
        }

        $io->success('Queued '.$count.' job(s)');

        return 0;
    }
}
