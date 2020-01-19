<?php

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\ContractApi;
use App\Document\Contract;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateContractTariffRate extends Command
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
     * @var ContractApi
     */
    private $contractApi;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param ContractApi            $contractApi
     * @param LoggerInterface        $logger
     */
    public function __construct(DocumentManager $documentManager, EntityManagerInterface $entityManager, ContractApi $contractApi, LoggerInterface $logger)
    {
        parent::__construct();

        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->contractApi = $contractApi;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-contract-tariff-rate')
            ->setDescription('Migrate contract tariff rate by applicationId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The contract id to be migrated', null)
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
            $qb = $this->documentManager->createAggregationBuilder(Contract::class);
            $contract = $qb->hydrate(Contract::class)
                ->match()
                ->field('_contractId')
                ->equals($id)
                ->project()
                ->includeFields(['_contractId', 'promotion_code_id'])
                ->lookup('promotion_codes')
                ->localField('promotion_code_id')
                ->foreignField('_id')
                ->alias('promotion_code_id')
                ->execute();

            if (\iter\count($contract) > 0) {
                $progressBar = new ProgressBar($output);
                $this->contractApi->updateContractTariffRate($contract);
                foreach ($contract as $contractData) {
                    $progressBar->advance();
                    $io->success('Migrated all details of the contract tariff rate '.$contractData->getContractId());
                    $progressBar->finish();
                }
            } else {
                $io->error('Application request not found');
            }
        } elseif (empty($id)) {
            $qb = $this->documentManager->createAggregationBuilder(Contract::class);
            $contractDocument = $qb->hydrate(Contract::class)
                ->project()
                ->includeFields(['_contractId', 'promotion_code_id'])
                ->lookup('promotion_codes')
                ->localField('promotion_code_id')
                ->foreignField('_id')
                ->alias('promotion_code_id')
                ->execute();
            if (\count($contractDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all contract tariff rates...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating contract tariff rates..... ');
                $this->contractApi->updateContractTariffRate($contractDocument);
                $progressBar->advance();
                $io->success('Migrated all contract tariff rates.');
                $progressBar->finish();
            } else {
                $io->error('Application requests not found');
            }
        }

        return 0;
    }
}
