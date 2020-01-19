<?php
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 9/1/19
 * Time: 11:29 AM.
 */

declare(strict_types=1);

namespace App\Bridge\Command;

use App\Bridge\Services\CustomerApi;
use App\Document\OldCustomerAccount;
use App\Document\OldCustomerIds;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCustomer extends Command
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
            ->setName('app:bridge:migrate-customer')
            ->setDescription('Migrates customer details by customerId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The customer id to be migrated', null)
            ->addOption('override', null, null, 'If the customer should be overwritten in db')
            ->addOption('details', 'd', null, 'To migrate all other details of customer')
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
                if (false === $input->getOption('override')) {
                    $this->customerApi->createCustomer([$customer]);
                    $this->logger->info(\sprintf('Migrated customer %s ...', $customer->getCustomerId()));
                    $io->success('Migrated all details of the customer account '.$customer->getCustomerId());
                } else {
                    $this->customerApi->updateCustomer([$customer]);
                    $this->logger->info(\sprintf('Migrated and updated customer %s ...', $customer->getCustomerId()));
                    $io->success('Migrated and updated all details of the customer account '.$customer->getCustomerId());
                }
                $progressBar->finish();
            } else {
                $io->error('Customer not found');
            }
        } elseif (empty($id)) {
            $customerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findAll();
            $oldCustomerIds = $this->documentManager->getRepository(OldCustomerIds::class)->findAll();

            if (\count($customerDocument) > 0) {
                $this->logger->info(\sprintf('Migrating all customers ...'));

                if (false !== $input->getOption('override')) {
                    $progressBar = new ProgressBar($output);
                    $io->text('Migrating customers ..... ');
                    $progressBar->advance();
                    $this->customerApi->updateCustomer($customerDocument);
                    $io->success('Migrated and updated all customer .');
                    $progressBar->finish();
                } elseif (false !== $input->getOption('details')) {
                    $progressBar = new ProgressBar($output);
                    $io->text('Migrating customer ids ..... ');
                    $progressBar->advance();
                    foreach ($oldCustomerIds as $oldCustomerId) {
                        $this->customerApi->createIds($oldCustomerId);
                    }
                    $io->success('Migrated all customer ids.');
                    $progressBar->finish();

                    $progressBar = new ProgressBar($output);
                    $io->text('Migrating customers activity ..... ');
                    $progressBar->advance();
                    $this->customerApi->createCustomerActivity($customerDocument);
                    $io->success('Migrated all customers activity.');
                    $progressBar->finish();

                    $progressBar = new ProgressBar($output);
                    $io->text('Migrating customers address ..... ');
                    $progressBar->advance();
                    $this->customerApi->createCustomerAddress($customerDocument);
                    $io->success('Migrated all customers address ...');
                    $progressBar->finish();

                    $progressBar = new ProgressBar($output);
                    $io->text('Migrating customers attachment ..... ');
                    $progressBar->advance();
                    $this->customerApi->createCustomerAttachment($customerDocument);
                    $io->success('Migrated all customers attachment ..... ');
                    $progressBar->finish();

                    $progressBar = new ProgressBar($output);
                    $io->text('Migrating customers note ..... ');
                    $progressBar->advance();
                    $this->customerApi->createCustomerNote($customerDocument);
                    $io->success('Migrated all customers note.');
                    $progressBar->finish();

                    $progressBar = new ProgressBar($output);
                    $io->text('Migrating customers agent and creator ..... ');
                    $progressBar->advance();
                    $this->customerApi->createCustomerAgentCreator($customerDocument);
                    $io->success('Migrated all customers agent and creator .');
                    $progressBar->finish();

                    $progressBar = new ProgressBar($output);
                    $io->text('Migrating customers identification ..... ');
                    $progressBar->advance();
                    $this->customerApi->createCustomerIdentifier($customerDocument);
                    $io->success('Migrated all customers identification.');
                    $io->success('Migrated all customers details.');
                    $progressBar->finish();
                } else {
                    $progressBar = new ProgressBar($output);
                    $io->text('Migrating customers ..... ');
                    $progressBar->advance();
                    $this->customerApi->createCustomer($customerDocument);
                    $io->success('Migrated all customer .');
                    $progressBar->finish();
                }
            } else {
                $io->error('Customers not found');
            }
        }

        return 0;
    }

    protected function migrateCustomer(OldCustomerAccount $customerDocument)
    {
    }
}
