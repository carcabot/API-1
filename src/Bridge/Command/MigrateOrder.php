<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\OrderApi;
use App\Document\RedemptionOrder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateOrder extends Command
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
     * @var OrderApi
     */
    private $orderApi;

    /**
     * @param DocumentManager $documentManager
     * @param LoggerInterface $logger
     * @param OrderApi        $orderApi
     */
    public function __construct(DocumentManager $documentManager, LoggerInterface $logger, OrderApi $orderApi)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->orderApi = $orderApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-order')
            ->setDescription('Migrate order details')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The order id to be migrated', null)
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
            $redemptionOrderDocument = $this->documentManager->getRepository(RedemptionOrder::class)->findOneBy(['id' => $id]);

            if (null !== $redemptionOrderDocument) {
                $progressBar = new ProgressBar($output);

                $this->orderApi->createOrders([$redemptionOrderDocument]);
                $this->logger->info(\sprintf('Migrated order %s ...', $redemptionOrderDocument->getOrderNumber()));

                $progressBar->advance();
                $io->success('Migrated all details of the order '.$redemptionOrderDocument->getOrderNumber());
                $progressBar->finish();
            } else {
                $io->error('Order not found');
            }
        } elseif (empty($id)) {
            $redemptionOrderDocument = $this->documentManager->getRepository(RedemptionOrder::class)->findAll();
            if (\count($redemptionOrderDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all orders ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating orders ..... ');
                $this->orderApi->createOrders($redemptionOrderDocument);
                $progressBar->advance();
                $io->success('Migrated all orders.');
                $progressBar->finish();
            } else {
                $io->error('Orders not found');
            }
        }

        return 0;
    }
}
