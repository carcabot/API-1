<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\OrderUpdateDatesApi;
use App\Document\RedemptionOrder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateRedeemCreditsActionDates extends Command
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
     * @var OrderUpdateDatesApi
     */
    private $orderUpdateDatesApi;

    /**
     * @param DocumentManager     $documentManager
     * @param LoggerInterface     $logger
     * @param OrderUpdateDatesApi $orderUpdateDatesApi
     */
    public function __construct(DocumentManager $documentManager, LoggerInterface $logger, OrderUpdateDatesApi $orderUpdateDatesApi)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->orderUpdateDatesApi = $orderUpdateDatesApi;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:update-redeem-credits-action-dates')
            ->setDescription('Updates redeem credits action dates.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $redemptionOrderDocument = $this->documentManager->getRepository(RedemptionOrder::class)->findAll();

        $progressBar = new ProgressBar($output);

        if (\count($redemptionOrderDocument) > 0) {
            $this->logger->info(\sprintf('Updating all order dates ...'));
            $io->text('Updating order dates ..... ');
            $this->orderUpdateDatesApi->createOrders($redemptionOrderDocument);
            $progressBar->advance();
            $io->success('Updated all order dates.');
        } else {
            $io->error('Orders not found');

            return -1;
        }

        $progressBar->finish();

        return 0;
    }
}
