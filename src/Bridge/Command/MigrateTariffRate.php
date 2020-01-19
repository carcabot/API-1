<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\TariffApi;
use App\Document\Tariff;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateTariffRate extends Command
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TariffApi
     */
    private $tariffApi;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DocumentManager        $documentManager
     * @param LoggerInterface        $logger
     * @param TariffApi              $tariffApi
     */
    public function __construct(EntityManagerInterface $entityManager, DocumentManager $documentManager, LoggerInterface $logger, TariffApi $tariffApi)
    {
        parent::__construct();
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->tariffApi = $tariffApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-tariff-rate')
            ->setDescription('Migrates tariff details by id')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The tariff id to be migrated', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $id = (string) $input->getOption('id');

        if (!empty($id)) {
            $tariff = $this->documentManager->getRepository(Tariff::class)->findOneBy(['id' => $id]);

            if (null !== $tariff) {
                $progressBar = new ProgressBar($output);

                $this->tariffApi->updateTariff([$tariff]);
                $this->logger->info(\sprintf('Migrated tariff %s ...', $tariff->getTariffCode()));

                $progressBar->advance();
                $io->success('Migrated all details of the tariff '.$tariff->getTariffCode());
                $progressBar->finish();
            } else {
                $io->error('Tariff not found');
            }
        } elseif (empty($id)) {
            $tariffDocument = $this->documentManager->getRepository(Tariff::class)->findAll();

            if (\count($tariffDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all tariff  ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating tariffs ..... ');
                $progressBar->advance();
                $this->tariffApi->updateTariff($tariffDocument);
                $io->success('Migrated all tariffs .');
                $progressBar->finish();
            } else {
                $io->error('Tariffs not found');
            }
        }

        return 0;
    }
}
