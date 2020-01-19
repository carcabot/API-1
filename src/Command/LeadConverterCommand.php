<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Lead;
use App\Enum\LeadStatus;
use App\Repository\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LeadConverterCommand extends Command
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
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:lead:convert')
            ->setDescription('Converts lead status if they have been turned to a customer .')
            ->addOption('customerId', null, InputOption::VALUE_REQUIRED, 'The account number of the customer the lead is converted to', null)

            ->setHelp(<<<'EOF'
The %command.name% command converts lead status when turned to a customer.
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
        $customerId = $input->getOption('customerId');

        if (null !== $customerId) {
            $leadRepository = $this->entityManager->getRepository(Lead::class);

            $io->text('Checking for leads with matching name & identification value of the customer ...');
            if ($leadRepository instanceof LeadRepository) {
                $leads = $leadRepository->findLeads((string) $customerId);
                if (\count($leads) > 0) {
                    $io->text('Leads found and converting their status ...');
                    foreach ($leads as $lead) {
                        $lead->setStatus(new LeadStatus(LeadStatus::CONVERTED));
                        $this->entityManager->persist($lead);
                    }
                } else {
                    $io->error('Leads not found');
                }
                $this->entityManager->flush();
            }
        } else {
            $io->error('Customer id cannot be null, provide a valid number');
        }

        return 0;
    }
}
