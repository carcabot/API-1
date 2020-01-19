<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Module;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateModulesCommand extends Command
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
            ->setName('app:modules:create')
            ->setDescription('Load modules');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $arguments = [
            '--env' => 'module',
            '--append' => true,
        ];
        $this->manualTruncateModuleTable();
        $this->loadFixtures($output, $arguments);

        return 0;
    }

    private function loadFixtures(OutputInterface $output, array $arg)
    {
        $command = $this->getApplication()->find('hautelook:fixtures:load');

        $input = new ArrayInput($arg);
        $returnCode = $command->run($input, $output);

        return $returnCode;
    }

    private function manualTruncateModuleTable()
    {
        $cmd = $this->entityManager->getClassMetadata(Module::class);
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
