<?php

declare(strict_types=1);

namespace App\Command;

use App\Disque\JobType;
use App\Entity\EarnContractCreditsAction;
use App\Entity\ReferralCreditsScheme;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateEarnContractsCreditsActionsInstruments extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DisqueQueue
     */
    private $migrationQueue;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DisqueQueue            $migrationQueue
     */
    public function __construct(EntityManagerInterface $entityManager, DisqueQueue $migrationQueue)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->migrationQueue = $migrationQueue;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:update-earn-contract-credits-action-instrument')
            ->addOption('noqueue', null, InputOption::VALUE_NONE, 'Dont queue the job to run.')
            ->setDescription('Update the earn credits action\'s instrument.')
            ->setHelp(<<<'EOF'
The %command.name% command processes Account Closure xml.
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
        $io->text('Finding referrals...');

        $contractsNumbers = [];
        $count = 0;
        $table = [];

        $qb = $this->entityManager->getRepository(EarnContractCreditsAction::class)->createQueryBuilder('e');
        $earnContractCreditsActions = $qb
            ->join('e.scheme', 's')
            ->where($qb->expr()->isNull('e.instrument'))
            ->andWhere($qb->expr()->eq('s.schemeId', $qb->expr()->literal('RF')))
            ->getQuery()
            ->getResult();

        foreach ($earnContractCreditsActions as $earnContractCreditsAction) {
            if ($earnContractCreditsAction->getScheme() instanceof ReferralCreditsScheme) {
                ++$count;

                $table[] = [
                    $earnContractCreditsAction->getId(),
                    $earnContractCreditsAction->getObject()->getContractNumber(),
                    $earnContractCreditsAction->getObject()->getCustomer()->getAccountNumber(),
                ];

                if (false === $input->getOption('noqueue')) {
                    $this->migrationQueue->push(new DisqueJob([
                        'data' => [
                            'id' => $earnContractCreditsAction->getId(),
                            'count' => $count,
                        ],
                        'type' => JobType::UPDATE_REFERRAL_EARNING_INSTRUMENT,
                    ]));
                }
            }
        }

        $io->table(['ID', 'Contract Number', 'Customer ID'], $table);
        if (false === $input->getOption('noqueue')) {
            $io->success(\sprintf('Queued %s referrals for updates.', $count));
        } else {
            $io->success(\sprintf('Found %s referrals for updates.', $count));
        }

        return 0;
    }
}
