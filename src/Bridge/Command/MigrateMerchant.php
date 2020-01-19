<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\MerchantApi;
use App\Document\ProductPartner;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateMerchant extends Command
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
     * @var MerchantApi
     */
    private $merchantApi;

    /**
     * @param DocumentManager $documentManager
     * @param LoggerInterface $logger
     * @param MerchantApi     $merchantApi
     */
    public function __construct(DocumentManager $documentManager, LoggerInterface $logger, MerchantApi $merchantApi)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->merchantApi = $merchantApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-merchant')
            ->setDescription('Migrate merchant details')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The merchant id to be migrated', null)
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
            $merchantDocument = $this->documentManager->getRepository(ProductPartner::class)->findOneBy(['id' => $id]);

            if (null !== $merchantDocument) {
                $progressBar = new ProgressBar($output);

                $this->merchantApi->createMerchants([$merchantDocument]);
                $this->logger->info(\sprintf('Migrated merchant %s ...', $merchantDocument->getCode()));

                $progressBar->advance();
                $io->success('Migrated all details of the merchant '.$merchantDocument->getCode());
                $progressBar->finish();
            } else {
                $io->error('Merchant not found');
            }
        } elseif (empty($id)) {
            $merchantDocuments = $this->documentManager->getRepository(ProductPartner::class)->findAll();
            if (\count($merchantDocuments) > 0) {
                $this->logger->info(\sprintf('Migrating all merchants ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating merchants ..... ');
                $this->merchantApi->createMerchants($merchantDocuments);
                $progressBar->advance();
                $io->success('Migrated all merchants.');
                $progressBar->finish();
            } else {
                $io->error('Merchants not found');
            }
        }

        return 0;
    }
}
