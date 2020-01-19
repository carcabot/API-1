<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\CreditsSchemeApi;
use App\Document\PointType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCreditsScheme extends Command
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CreditsSchemeApi
     */
    private $creditsSchemeApi;

    /**
     * @param DocumentManager  $documentManager
     * @param LoggerInterface  $logger
     * @param CreditsSchemeApi $creditsSchemeApi
     */
    public function __construct(DocumentManager $documentManager, LoggerInterface $logger, CreditsSchemeApi $creditsSchemeApi)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->creditsSchemeApi = $creditsSchemeApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-credits-scheme')
            ->setDescription('Migrate credits scheme details')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The credits scheme id to be migrated', null)
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
            $pointTypeDocument = $this->documentManager->getRepository(PointType::class)->findOneBy(['id' => $id]);

            if (null !== $pointTypeDocument) {
                $progressBar = new ProgressBar($output);

                $this->creditsSchemeApi->createCreditsSchemes([$pointTypeDocument]);
                $this->logger->info(\sprintf('Migrated credits scheme %s ...', $pointTypeDocument->getPointId()));

                $progressBar->advance();
                $io->success('Migrated all details of the credits scheme '.$pointTypeDocument->getPointId());
                $progressBar->finish();
            } else {
                $io->error('Credits scheme not found');
            }
        } elseif (empty($id)) {
            $pointTypeDocuments = $this->documentManager->getRepository(PointType::class)->findAll();
            if (\count($pointTypeDocuments) > 0) {
                $this->logger->info(\sprintf('Migrating all credits schemes ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating credits schemes ..... ');
                $this->creditsSchemeApi->createCreditsSchemes($pointTypeDocuments);
                $progressBar->advance();
                $io->success('Migrated all credits schemes.');
                $progressBar->finish();
            } else {
                $io->error('Credits schemes not found');
            }
        }

        return 0;
    }
}
