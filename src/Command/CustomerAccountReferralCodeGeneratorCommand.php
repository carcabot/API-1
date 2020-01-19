<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\CustomerAccount;
use App\Model\CustomerAccountReferralCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CustomerAccountReferralCodeGeneratorCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerAccountReferralCodeGenerator
     */
    private $referralCodeGenerator;

    /**
     * @param EntityManagerInterface               $entityManager
     * @param LoggerInterface                      $logger
     * @param CustomerAccountReferralCodeGenerator $referralCodeGenerator
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, CustomerAccountReferralCodeGenerator $referralCodeGenerator)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->referralCodeGenerator = $referralCodeGenerator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:customer:generate-referral-code')
            ->setDescription('Generates a referral code for a CustomerAccount.')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'id of CustomerAccount', null)
            ->setHelp(<<<'EOF'
The %command.name% command generates a referral code.
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
        if (null !== $id) {
            $customer = $this->entityManager->getRepository(CustomerAccount::class)->find((int) $id);

            if (null !== $customer) {
                $io->text('Customer Account, generating referral code...');

                $referralCode = $this->referralCodeGenerator->generateReferralCode($customer);

                $customer->setReferralCode($referralCode);

                $this->entityManager->persist($customer);
                $this->entityManager->flush();

                return 0;
            }

            $io->text('Customer Account cannot be found.');

            return 0;
        }

        $qb = $this->entityManager->getRepository(CustomerAccount::class)->createQueryBuilder('customer');
        $expr = $qb->expr();

        $customerAccounts = $qb->where($expr->isNull('customer.referralCode'))->getQuery()->getResult();

        if (\count($customerAccounts) > 0) {
            $io->text('Generating referral code for all Customers.');
            foreach ($customerAccounts as $customerAccount) {
                $referralCode = $this->referralCodeGenerator->generateReferralCode($customerAccount);

                $customerAccount->setReferralCode($referralCode);

                $this->entityManager->persist($customerAccount);
            }

            $this->entityManager->flush();

            return 0;
        }

        $io->text('All Customers have referral code.');

        return 0;
    }
}
