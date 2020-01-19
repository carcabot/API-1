<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\OfferApi;
use App\Document\Product;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateOffer extends Command
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
     * @var OfferApi
     */
    private $offerApi;

    /**
     * @param DocumentManager $documentManager
     * @param LoggerInterface $logger
     * @param OfferApi        $offerApi
     */
    public function __construct(DocumentManager $documentManager, LoggerInterface $logger, OfferApi $offerApi)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->offerApi = $offerApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-offer')
            ->setDescription('Migrate offer details')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The offer id to be migrated', null)
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
            $product = $this->documentManager->getRepository(Product::class)->findOneBy(['id' => $id]);

            if (null !== $product) {
                $progressBar = new ProgressBar($output);

                $this->offerApi->createOffers([$product]);
                $this->logger->info(\sprintf('Migrated offer %s ...', $product->getProductNumber()));

                $progressBar->advance();
                $io->success('Migrated all details of the offer '.$product->getProductNumber());
                $progressBar->finish();
            } else {
                $io->error('Offer not found');
            }
        } elseif (empty($id)) {
            $offerDocuments = $this->documentManager->getRepository(Product::class)->findAll();
            if (\count($offerDocuments) > 0) {
                $this->logger->info(\sprintf('Migrating all offers ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating offers ..... ');
                $this->offerApi->createOffers($offerDocuments);
                $progressBar->advance();
                $io->success('Migrated all offers.');
                $progressBar->finish();
            } else {
                $io->error('Offers not found');
            }
        }

        return 0;
    }
}
