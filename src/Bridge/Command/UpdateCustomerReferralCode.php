<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 1/3/19
 * Time: 3:01 PM.
 */

namespace App\Bridge\Command;

use App\Bridge\Services\CustomerApi;
use App\Document\OldCustomerAccount;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCustomerReferralCode extends Command
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
     * @param CustomerApi            $customerApi
     * @param EntityManagerInterface $entityManager
     * @param DocumentManager        $documentManager
     */
    public function __construct(CustomerApi $customerApi, EntityManagerInterface $entityManager, DocumentManager $documentManager)
    {
        parent::__construct();
        $this->customerApi = $customerApi;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:bridge:update-customer-referral')
            ->setDescription('Updates customer referral by customerId')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The customer id to be updated', null)
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
                $this->customerApi->updateCustomerReferralCode([$customer]);

                $io->success('Updated the referral code of customer account '.$customer->getCustomerId());
                $progressBar->finish();
            } else {
                $io->error('Customer not found');
            }
        } elseif (empty($id)) {
            $customerDocument = $this->documentManager->getRepository(OldCustomerAccount::class)->findAll();

            if (\count($customerDocument) > 0) {
                $progressBar = new ProgressBar($output);
                $io->text('Updating customers referral code..... ');
                $progressBar->advance();
                $this->customerApi->updateCustomerReferralCode($customerDocument);
                $progressBar->finish();
                $io->success('Updated all customers referral code.');
            } else {
                $io->error('Customers not found');
            }
        }

        return 0;
    }
}
