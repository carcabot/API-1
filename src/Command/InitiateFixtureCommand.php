<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitiateFixtureCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:fixture:load')
            ->setDescription('Load fixtures')
            ->addOption('option', null, InputOption::VALUE_OPTIONAL, 'Enter loading option', null)
            ->addOption('record', null, InputOption::VALUE_REQUIRED, 'Be aware of expected records will be multiply by 100 for each table. Except Tariff Rate only 50.', null)
            ->setHelp(<<<'EOF'
The %command.name% command starts the worker.
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
        $option = $input->getOption('option');
        $recordNumber = $input->getOption('record');

        if (null !== $recordNumber && 0 < $recordNumber) {
            if (null !== $option) {
                if ('append' === $option) {
                    for ($i = 0; $i < $recordNumber; ++$i) {
                        $arguments1 = [
                            '--env' => 'group1',
                            '--append' => true,
                        ];
                        $this->loadFixtures($output, $arguments1, false);
                    }
                    for ($i = 0; $i < $recordNumber; ++$i) {
                        $arguments2 = [
                            '--env' => 'group2',
                            '--append' => true,
                        ];
                        $this->loadFixtures($output, $arguments2, false);
                    }
                } elseif ('purge-with-truncate' === $option) {
                    for ($i = 0; $i < $recordNumber; ++$i) {
                        if (0 === $i) {
                            $arguments1 = [
                                '--env' => 'group1',
                                '--purge-with-truncate' => true,
                            ];
                            $this->loadFixtures($output, $arguments1, true);
                        } else {
                            $arguments1 = [
                                '--env' => 'group1',
                                '--append' => true,
                            ];
                            $this->loadFixtures($output, $arguments1, false);
                        }
                    }
                    for ($i = 0; $i < $recordNumber; ++$i) {
                        $arguments2 = [
                            '--env' => 'group2',
                            '--append' => true,
                        ];
                        $this->loadFixtures($output, $arguments2, false);
                    }
                }
            } else {
                for ($i = 0; $i < $recordNumber; ++$i) {
                    if (0 === $i) {
                        $arguments1 = [
                            '--env' => 'group1',
                        ];
                        $this->loadFixtures($output, $arguments1, true);
                    } else {
                        $arguments1 = [
                            '--env' => 'group1',
                            '--append' => true,
                        ];
                        $this->loadFixtures($output, $arguments1, false);
                    }
                }
                for ($i = 0; $i < $recordNumber; ++$i) {
                    $arguments2 = [
                        '--env' => 'group2',
                        '--append' => true,
                    ];
                    $this->loadFixtures($output, $arguments2, false);
                }
            }
        } else {
            $io->error('Please specify number of record with option --record. Record must be higher than 0.');
        }

        return 0;
    }

    private function loadFixtures(OutputInterface $output, array $arg, bool $purgeMode)
    {
        $command = $this->getApplication()->find('hautelook:fixtures:load');

        if (true === $purgeMode) {
            $this->manualTruncateApplicationRequestTable();
            $this->manualTruncateContractTable();
        }

        $input = new ArrayInput($arg);
        $returnCode = $command->run($input, $output);

        return $returnCode;
    }

    private function manualTruncateContractTable()
    {
        $cmd = $this->entityManager->getClassMetadata(Contract::class);
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $connection->query('DELETE FROM '.$cmd->getTableName());
            // Beware of ALTER TABLE here--it's another DDL statement and will cause
            // an implicit commit.
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
        }
    }

    private function manualTruncateApplicationRequestTable()
    {
        $cmd = $this->entityManager->getClassMetadata(ApplicationRequest::class);
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $connection->query('DELETE FROM '.$cmd->getTableName());
            // Beware of ALTER TABLE here--it's another DDL statement and will cause
            // an implicit commit.
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
        }
    }
}
