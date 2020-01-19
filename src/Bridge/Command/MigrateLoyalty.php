<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\CreditsSchemeApi;
use App\Bridge\Services\EarnCreditsActionApi;
use App\Bridge\Services\MerchantApi;
use App\Bridge\Services\OfferApi;
use App\Bridge\Services\OfferCatalogApi;
use App\Bridge\Services\OfferCategoryApi;
use App\Bridge\Services\OrderApi;
use App\Document\OldOrderIds;
use App\Document\PointHistory;
use App\Document\PointType;
use App\Document\Product;
use App\Document\ProductCatalog;
use App\Document\ProductCategory;
use App\Document\ProductPartner;
use App\Document\RedemptionOrder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateLoyalty extends Command
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var CreditsSchemeApi
     */
    private $creditsSchemeApi;

    /**
     * @var EarnCreditsActionApi
     */
    private $earnCreditsActionApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MerchantApi
     */
    private $merchantApi;

    /**
     * @var OfferCatalogApi
     */
    private $offerCatalogApi;

    /**
     * @var OfferCategoryApi
     */
    private $offerCategoryApi;

    /**
     * @var OfferApi
     */
    private $offerApi;

    /**
     * @var OrderApi
     */
    private $orderApi;

    /**
     * @param DocumentManager      $documentManager
     * @param CreditsSchemeApi     $creditsSchemeApi
     * @param EarnCreditsActionApi $earnCreditsActionApi
     * @param LoggerInterface      $logger
     * @param MerchantApi          $merchantApi
     * @param OfferCatalogApi      $offerCatalogApi
     * @param OfferCategoryApi     $offerCategoryApi
     * @param OfferApi             $offerApi
     * @param OrderApi             $orderApi
     */
    public function __construct(DocumentManager $documentManager, CreditsSchemeApi $creditsSchemeApi, EarnCreditsActionApi $earnCreditsActionApi, LoggerInterface $logger, MerchantApi $merchantApi, OfferCatalogApi $offerCatalogApi, OfferCategoryApi $offerCategoryApi, OfferApi $offerApi, OrderApi $orderApi)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->creditsSchemeApi = $creditsSchemeApi;
        $this->earnCreditsActionApi = $earnCreditsActionApi;
        $this->logger = $logger;
        $this->merchantApi = $merchantApi;
        $this->offerCatalogApi = $offerCatalogApi;
        $this->offerCategoryApi = $offerCategoryApi;
        $this->offerApi = $offerApi;
        $this->orderApi = $orderApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-loyalty')
            ->setDescription('Migrate earn credits action details')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $offerCategoryDocuments = $this->documentManager->getRepository(ProductCategory::class)->findAll();
        $merchantDocuments = $this->documentManager->getRepository(ProductPartner::class)->findAll();
        $offerCatalogDocuments = $this->documentManager->getRepository(ProductCatalog::class)->findAll();
        $offerDocuments = $this->documentManager->getRepository(Product::class)->findAll();
        $oldOrderIds = $this->documentManager->getRepository(OldOrderIds::class)->findAll();
        $redemptionOrderDocument = $this->documentManager->getRepository(RedemptionOrder::class)->findAll();
        $pointTypeDocuments = $this->documentManager->getRepository(PointType::class)->findAll();
        $earnCreditsActionDocuments = $this->documentManager->getRepository(PointHistory::class)->findAll();

        $progressBar = new ProgressBar($output);

        if (\count($offerCategoryDocuments) > 0) {
            $this->logger->info(\sprintf('Migrating all offer categories ...'));
            $io->text('Migrating offer categories ..... ');
            $this->offerCategoryApi->createOfferCategories($offerCategoryDocuments);
            $progressBar->advance();
            $io->success('Migrated all offer categories.');
        } else {
            $io->error('Offer categories not found');

            return -1;
        }

        if (\count($merchantDocuments) > 0) {
            $this->logger->info(\sprintf('Migrating all merchants ...'));

            $io->text('Migrating merchants ..... ');
            $this->merchantApi->createMerchants($merchantDocuments);
            $progressBar->advance();
            $io->success('Migrated all merchants.');
        } else {
            $io->error('Merchants not found');

            return -1;
        }

        if (\count($offerCatalogDocuments) > 0) {
            $this->logger->info(\sprintf('Migrating all offer catalogs ...'));
            $io->text('Migrating offer catalogs ..... ');
            $this->offerCatalogApi->createOfferCatalogs($offerCatalogDocuments);
            $progressBar->advance();
            $io->success('Migrated all offer catalogs.');
        } else {
            $io->error('Offer catalogs not found');

            return -1;
        }

        if (\count($offerDocuments) > 0) {
            $this->logger->info(\sprintf('Migrating all offers ...'));
            $io->text('Migrating offers ..... ');
            $this->offerApi->createOffers($offerDocuments);
            $progressBar->advance();
            $io->success('Migrated all offers.');
        } else {
            $io->error('Offers not found');

            return -1;
        }

        if (\count($oldOrderIds) > 0) {
            $this->logger->info(\sprintf('Migrating all ids ...'));
            $io->text('Migrating ids ..... ');
            foreach ($oldOrderIds as $oldOrderId) {
                $this->orderApi->createIds($oldOrderId);
            }
            $progressBar->advance();
            $io->success('Migrated all ids.');
        } else {
            $io->error('Offers not found');

            return -1;
        }

        if (\count($redemptionOrderDocument) > 0) {
            $this->logger->info(\sprintf('Migrating all orders ...'));
            $io->text('Migrating orders ..... ');
            $this->orderApi->createOrders($redemptionOrderDocument);
            $progressBar->advance();
            $io->success('Migrated all orders.');
        } else {
            $io->error('Orders not found');

            return -1;
        }

        if (\count($pointTypeDocuments) > 0) {
            $this->logger->info(\sprintf('Migrating all credits schemes ...'));
            $io->text('Migrating credits schemes ..... ');
            $this->creditsSchemeApi->createCreditsSchemes($pointTypeDocuments);
            $progressBar->advance();
            $io->success('Migrated all credits schemes.');
        } else {
            $io->error('Credits scheme not found');

            return -1;
        }

        if (\count($earnCreditsActionDocuments) > 0) {
            $this->logger->info(\sprintf('Migrating all earn credits actions ...'));
            $io->text('Migrating earn credits actions ..... ');
            $this->earnCreditsActionApi->createEarnCreditsActions($earnCreditsActionDocuments);
            $progressBar->advance();
            $io->success('Migrated all earn credits actions.');
        } else {
            $io->error('Earn credits actions not found');

            return -1;
        }

        $progressBar->finish();

        return 0;
    }
}
