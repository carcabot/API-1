<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Command\PartnerCommissionStatement\UpdateEndDate;
use App\Entity\CustomerAccount;
use App\Entity\Partner;
use App\Entity\PartnerCommissionStatement;
use App\Entity\QuantitativeValue;
use App\Enum\CommissionStatementStatus;
use App\Model\PartnerCommissionProcessor;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TriggerPartnerCommissionStatement extends Command
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PartnerCommissionProcessor
     */
    private $partnerCommissionProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param CommandBus                 $commandBus
     * @param EntityManagerInterface     $entityManager
     * @param PartnerCommissionProcessor $partnerCommissionProcessor
     * @param LoggerInterface            $logger
     * @param string                     $timezone
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, PartnerCommissionProcessor $partnerCommissionProcessor, LoggerInterface $logger, string $timezone)
    {
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->partnerCommissionProcessor = $partnerCommissionProcessor;
        $this->logger = $logger;
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:trigger:partner-commission-statement')
            ->setDescription('Triggers a partner commission statement generation for a specified partner.')
            ->addOption('partner', null, InputOption::VALUE_REQUIRED, 'Partner', null)
            ->addOption('cycle', null, InputOption::VALUE_NONE, 'Indicates whether to start a payment cycle.')
            ->setHelp(<<<'EOF'
The %command.name% command triggers a partner commission statement generation for a specified partner.
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

        $partner = $input->getOption('partner');
        $cycle = (bool) $input->getOption('cycle');

        $partnerCustomerAccount = $this->entityManager->getRepository(CustomerAccount::class)->findOneBy(['partnerDetails' => $partner]);
        $partner = $this->entityManager->getRepository(Partner::class)->find($partner);

        if (null === $partner) {
            $io->error('Partner not found.');

            return 0;
        }

        $payoutStartDate = new \DateTime();

        $partner->setPayoutStartDate($payoutStartDate);
        $partner->setPayoutCycle(new QuantitativeValue('1', null, null, 'DAY'));
        $this->entityManager->persist($partner);
        $this->entityManager->flush();

        $commissionStatement = $this->entityManager->getRepository(PartnerCommissionStatement::class)->findOneBy([
            'partner' => $partner->getId(),
            'status' => new CommissionStatementStatus(CommissionStatementStatus::NEW),
        ]);

        $payoutStartDate = new \DateTime($payoutStartDate->format('Y-m-d'));
        $payoutStartDate->setTimezone($this->timezone)->setTime(0, 0, 0)->setTimezone(new \DateTimeZone('UTC'));

        if (null !== $commissionStatement) {
            $this->commandBus->handle(new UpdateEndDate($commissionStatement, $payoutStartDate, $this->timezone));
        } else {
            $commissionStatement = $this->partnerCommissionProcessor->createNewStatement($partner, $payoutStartDate);
        }

        $this->entityManager->persist($commissionStatement);
        $this->entityManager->flush();

        $this->partnerCommissionProcessor->generatePartnerCommissionStatement([
            'commissionStatementId' => $commissionStatement->getId(),
            'endDateTimestamp' => $commissionStatement->getEndDate()->getTimestamp(),
            'partnerId' => $commissionStatement->getPartner()->getId(),
        ], $cycle);

        return 0;
    }
}
