<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\CustomerAccount;
use App\WebService\Billing\ClientInterface as WebServiceClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCustomerContact extends Command
{
    /**
     * @var WebServiceClient
     */
    private $webServiceClient;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param WebServiceClient       $webServiceClient
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(WebServiceClient $webServiceClient, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();

        $this->webServiceClient = $webServiceClient;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:web-service:update-customer-contact')
            ->setDescription('Update customer contact information.')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Trigger webservice for which customer', null)
            ->setHelp(<<<'EOF'
The %command.name% command update customer contact information.
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

        $customerAccount = $this->entityManager->getRepository(CustomerAccount::class)->find($id);

        if (null === $customerAccount) {
            $customerAccount = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['accountNumber' => $id]);
        }

        if (null !== $customerAccount) {
            $result = $this->webServiceClient->updateCustomerContact($customerAccount);

            $io->text($result);
        }

        return 0;
    }
}
