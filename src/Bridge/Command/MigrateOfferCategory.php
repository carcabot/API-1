<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\OfferCategoryApi;
use App\Document\ProductCategory;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateOfferCategory extends Command
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
     * @var OfferCategoryApi
     */
    private $offerCategoryApi;

    /**
     * @param DocumentManager  $documentManager
     * @param LoggerInterface  $logger
     * @param OfferCategoryApi $offerCategoryApi
     */
    public function __construct(DocumentManager $documentManager, LoggerInterface $logger, OfferCategoryApi $offerCategoryApi)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->offerCategoryApi = $offerCategoryApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-offer-category')
            ->setDescription('Migrate offer category details')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The offer category id to be migrated', null)
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
            $productCategory = $this->documentManager->getRepository(ProductCategory::class)->findOneBy(['id' => $id]);

            if (null !== $productCategory) {
                $progressBar = new ProgressBar($output);

                $this->offerCategoryApi->createOfferCategories([$productCategory]);
                $this->logger->info(\sprintf('Migrated offer category %s ...', $productCategory->getCode()));

                $progressBar->advance();
                $io->success('Migrated all details of the offer category '.$productCategory->getCode());
                $progressBar->finish();
            } else {
                $io->error('Offer category not found');
            }
        } elseif (empty($id)) {
            $offerCategoryDocuments = $this->documentManager->getRepository(ProductCategory::class)->findAll();
            if (\count($offerCategoryDocuments) > 0) {
                $this->logger->info(\sprintf('Migrating all offer categories ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating offer categories ..... ');
                $this->offerCategoryApi->createOfferCategories($offerCategoryDocuments);
                $progressBar->advance();
                $io->success('Migrated all offer categories.');
                $progressBar->finish();
            } else {
                $io->error('Offer categories not found');
            }
        }

        return 0;
    }
}
