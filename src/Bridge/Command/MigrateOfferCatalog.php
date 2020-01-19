<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\OfferCatalogApi;
use App\Document\ProductCatalog;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateOfferCatalog extends Command
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
     * @var OfferCatalogApi
     */
    private $offerCatalogApi;

    /**
     * @param DocumentManager $documentManager
     * @param LoggerInterface $logger
     * @param OfferCatalogApi $offerCatalogApi
     */
    public function __construct(DocumentManager $documentManager, LoggerInterface $logger, OfferCatalogApi $offerCatalogApi)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->offerCatalogApi = $offerCatalogApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-offer-catalog')
            ->setDescription('Migrate offer catalogs details')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The offer catalog id to be migrated', null)
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
            $productCatalog = $this->documentManager->getRepository(ProductCatalog::class)->findOneBy(['id' => $id]);

            if (null !== $productCatalog) {
                $progressBar = new ProgressBar($output);

                $this->offerCatalogApi->createOfferCatalogs([$productCatalog]);
                $this->logger->info(\sprintf('Migrated offer catalog %s ...', $productCatalog->getName()));

                $progressBar->advance();
                $io->success('Migrated all details of the offer catalog '.$productCatalog->getName());
                $progressBar->finish();
            } else {
                $io->error('Offer catalog not found');
            }
        } elseif (empty($id)) {
            $offerCatalogDocuments = $this->documentManager->getRepository(ProductCatalog::class)->findAll();
            if (\count($offerCatalogDocuments) > 0) {
                $this->logger->info(\sprintf('Migrating all offer catalogs ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating offer catalogs ..... ');
                $this->offerCatalogApi->createOfferCatalogs($offerCatalogDocuments);
                $progressBar->advance();
                $io->success('Migrated all offer catalogs.');
                $progressBar->finish();
            } else {
                $io->error('Offer catalogs not found');
            }
        }

        return 0;
    }
}
