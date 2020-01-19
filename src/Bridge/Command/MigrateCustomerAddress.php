<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 28/1/19
 * Time: 3:00 PM.
 */

namespace App\Bridge\Command;

use App\Bridge\Services\CustomerApi;
use App\Document\OldCustomerAccount;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCustomerAddress extends Command
{
    /**
     * @var CustomerApi
     */
    private $customerApi;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CustomerApi            $customerApi
     * @param EntityManagerInterface $entityManager
     * @param DocumentManager        $documentManager
     * @param LoggerInterface        $logger
     */
    public function __construct(CustomerApi $customerApi, EntityManagerInterface $entityManager, DocumentManager $documentManager, LoggerInterface $logger)
    {
        parent::__construct();
        $this->customerApi = $customerApi;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:migrate-customer-address')
            ->setDescription('Migrates customer address by customerId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The customer id to be migrated', null)
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
            $customer = $this->documentManager->getRepository(OldCustomerAccount::class)->findOneBy(['id' => $id]);

            if (null !== $customer) {
                $progressBar = new ProgressBar($output);

                $progressBar->advance();
                $this->customerApi->createCustomerAddress([$customer]);
                $this->logger->info(\sprintf('Migrated customer address of %s ...', $customer->getCustomerId()));

                $io->success('Migrated the address details of customer account '.$customer->getCustomerId());
                $progressBar->finish();
            } else {
                $io->error('Customer not found');
            }
        } elseif (empty($id)) {
            $customerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findAll();

            if (\count($customerDocument) > 0) {
                $this->logger->info(\sprintf('Migrating address of all customers ...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating customers address ..... ');
                $progressBar->advance();
                $this->customerApi->createCustomerAddress($customerDocument);
                $progressBar->finish();
                $io->success('Migrated all customers address ...');
            } else {
                $io->error('Customers not found');
            }
        }

        return 0;
    }
}
