<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 1/2/19
 * Time: 5:07 PM.
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

class MigrateCustomerIdentification extends Command
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
            ->setName('app:bridge:migrate-customer-identification')
            ->setDescription('Migrates customers identification details by customerId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The customer id to be migrated', null)
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
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
                $this->customerApi->createCustomerIdentifier([$customer]);
                $this->logger->info(\sprintf('Migrated identification of customer %s ...', $customer->getCustomerId()));

                $io->success('Migrated identification for the customer account '.$customer->getCustomerId());
                $progressBar->finish();
            } else {
                $io->error('Customer not found');
            }
        } elseif (empty($id)) {
            $customerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findAll();

            if (\count($customerDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all customers identification...'));

                $progressBar = new ProgressBar($output);
                $io->text('Migrating customers identification ..... ');
                $progressBar->advance();
                $this->customerApi->createCustomerIdentifier($customerDocument);
                $io->success('Migrated all customers identification.');
                $progressBar->finish();
            } else {
                $io->error('Customers not found');
            }
        }

        return 0;
    }
}
