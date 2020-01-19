<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\EarnCreditsActionApi;
use App\Document\PointHistory;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateEarnCreditsAction extends Command
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
     * @var EarnCreditsActionApi
     */
    private $earnCreditsActionApi;

    /**
     * @param DocumentManager      $documentManager
     * @param LoggerInterface      $logger
     * @param EarnCreditsActionApi $earnCreditsActionApi
     */
    public function __construct(DocumentManager $documentManager, LoggerInterface $logger, EarnCreditsActionApi $earnCreditsActionApi)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->earnCreditsActionApi = $earnCreditsActionApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-earn-credits-action')
            ->setDescription('Migrate earn credits action details')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $earnCreditsActionDocuments = $this->documentManager->getRepository(PointHistory::class)->findAll();
        if (\count($earnCreditsActionDocuments) > 0) {
            $this->logger->info(\sprintf('Migrating all earn credits actions ...'));

            $progressBar = new ProgressBar($output);
            $io->text('Migrating earn credits actions ..... ');
            $this->earnCreditsActionApi->createEarnCreditsActions($earnCreditsActionDocuments);
            $progressBar->advance();
            $io->success('Migrated all earn credits actions.');
            $progressBar->finish();
        } else {
            $io->error('Earn credits actions not found');
        }

        return 0;
    }
}
