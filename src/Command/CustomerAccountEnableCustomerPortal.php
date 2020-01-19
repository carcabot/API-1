<?php

declare(strict_types=1);

namespace App\Command;

use App\Disque\JobType;
use App\Entity\CustomerAccount;
use App\Model\CustomerAccountPortalEnableUpdater;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CustomerAccountEnableCustomerPortal extends Command
{
    /**
     * @var DisqueQueue
     */
    private $contractQueue;

    /**
     * @var CustomerAccountPortalEnableUpdater
     */
    private $customerAccountPortalEnableUpdater;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DisqueQueue                        $contractQueue
     * @param CustomerAccountPortalEnableUpdater $customerAccountPortalEnableUpdater
     * @param EntityManagerInterface             $entityManager
     * @param LoggerInterface                    $logger
     */
    public function __construct(DisqueQueue $contractQueue, CustomerAccountPortalEnableUpdater $customerAccountPortalEnableUpdater, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();

        $this->contractQueue = $contractQueue;
        $this->customerAccountPortalEnableUpdater = $customerAccountPortalEnableUpdater;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:customer:enable-customer-portal')
            ->setDescription('enable\disable customer portal for customer(s).')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'id of CustomerAccount', null)
            ->addOption('noqueue', null, InputOption::VALUE_NONE, 'Dont queue the job to run.')
            ->setHelp(<<<'EOF'
The %command.name% command enable\disable customer portal for customers.
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getOption('id');
        $customers = [];

        if (null !== $id) {
            $customer = $this->entityManager->getRepository(CustomerAccount::class)->find((int) $id);

            if (null !== $customer) {
                $io->text('update customerPortalEnabled for customer'.$customer->getId());
                $this->logger->info('update customerPortalEnabled for customer'.$customer->getId());
                $customers[] = $customer;
            } else {
                $io->text('Customer Account cannot be found.');
                $this->logger->error('Customer Account cannot be found.');
            }
        } else {
            $customers = $this->entityManager->getRepository(CustomerAccount::class)->findAll();
            $io->text('update customerPortalEnabled for all customers');
            $this->logger->info('update customerPortalEnabled for all customers.');
        }

        $count = 0;
        $total = \count($customers);

        foreach ($customers as $customer) {
            ++$count;
            if (false === $input->getOption('noqueue')) {
                $this->contractQueue->push(new DisqueJob([
                    'data' => [
                        'id' => $customer->getId(),
                        'count' => $count,
                        'maxCount' => $total,
                    ],
                    'type' => JobType::CUSTOMER_PORTAL_ENABLED_UPDATE,
                ]));
            }
        }

        if (false === $input->getOption('noqueue')) {
            $io->success(\sprintf('Queued %s customer(s) for updates.', $count));
        } else {
            $io->success(\sprintf('Found %s customer(s) for updates.', $count));
        }

        return 0;
    }
}
