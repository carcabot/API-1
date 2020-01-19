<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\MaintenanceConfiguration;
use App\Enum\MaintenanceConfigurationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MaintenanceConfigurationStatusConverter extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:maintenance-configuration:update-status')
            ->setDescription('Updates maintenance status if they are active or completed respectively')
            ->addOption('maintenanceId', 'id', InputOption::VALUE_REQUIRED, 'The id of maintenance that need to be updated', null)
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The type of update', null)
            ->setHelp(<<<'EOF'
The %command.name% command converts maintenance status accordingly.
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
        $maintenanceId = $input->getOption('maintenanceId');
        $updateType = $input->getOption('type');

        if (null !== $maintenanceId) {
            $maintenanceConfiguration = $this->entityManager->getRepository(MaintenanceConfiguration::class)->findOneBy(['id' => $maintenanceId]);
            if (null !== $maintenanceConfiguration) {
                if ('start' === $updateType) {
                    $maintenanceConfiguration->setStatus(new MaintenanceConfigurationStatus(MaintenanceConfigurationStatus::ACTIVE));
                } elseif ('end' === $updateType) {
                    $maintenanceConfiguration->setStatus(new MaintenanceConfigurationStatus(MaintenanceConfigurationStatus::COMPLETED));
                } else {
                    $io->error('Unsupported update type provided');
                }
                $this->entityManager->persist($maintenanceConfiguration);
                $this->entityManager->flush();
            } else {
                $io->error('No Maintenance found for given Id');
            }
        }

        return 0;
    }
}
