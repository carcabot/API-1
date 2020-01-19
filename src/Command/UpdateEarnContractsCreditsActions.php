<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Command\Contract\UpdatePointCreditsActions;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\ApplicationRequest;
use App\Entity\Contract;
use App\Entity\CreditsScheme;
use App\Entity\EarnContractCreditsAction;
use App\Entity\ReferralCreditsScheme;
use App\Enum\ActionStatus;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateEarnContractsCreditsActions extends Command
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
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:contract:update-earn-credits-action')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Which id to update.')
            ->setDescription('Update the contracts\' earn credits actions.')
            ->setHelp(<<<'EOF'
The %command.name% command processes the earn credits action.
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

        if (!empty($id)) {
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $id]);

            if (null === $contract) {
                $contract = $this->entityManager->getRepository(Contract::class)->find($id);
            }

            $qb = $this->entityManager->getRepository(ReferralCreditsScheme::class)->createQueryBuilder('credit');
            $expr = $qb->expr();

            /**
             * @var ReferralCreditsScheme[]
             */
            $referralSchemes = $qb->where($expr->lte('credit.validFrom', ':now'))
                ->andWhere($expr->gte('credit.validThrough', ':now'))
                ->andWhere($expr->isNull('credit.isBasedOn'))
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->getResult();

            // @todo Not safe checking, codes need to be more foolproof
            if (1 === \count($referralSchemes)) {
                if (null !== $contract && null !== ($referralCode = $contract->getCustomer()->getReferralCode())) {
                    $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('app');
                    $completedApplications = $qb->where($qb->expr()->eq('app.status', $qb->expr()->literal(ApplicationRequestStatus::COMPLETED)))
                        ->andWhere($qb->expr()->eq('app.type', $qb->expr()->literal(ApplicationRequestType::CONTRACT_APPLICATION)))
                        ->andWhere($qb->expr()->eq('app.referralCode', $qb->expr()->literal($referralCode)))
                        ->getQuery()
                        ->getResult();

                    $earnedContracts = [];

                    foreach ($contract->getPointCreditsActions() as $creditsAction) {
                        if ($creditsAction instanceof EarnContractCreditsAction &&
                            null !== $creditsAction->getScheme() &&
                            $creditsAction->getScheme() instanceof ReferralCreditsScheme
                        ) {
                            foreach ($completedApplications as $completedApplication) {
                                if (null !== $completedApplication->getContract() &&
                                    null !== $creditsAction->getInstrument() &&
                                    $creditsAction->getInstrument()->getId() === $completedApplication->getContract()->getId()
                                ) {
                                    $earnedContracts[] = $completedApplication->getId();
                                    break;
                                }
                            }
                        }
                    }

                    foreach ($completedApplications as $completedApplication) {
                        if (!\in_array($completedApplication->getId(), $earnedContracts, true)) {
                            if (null !== $completedApplication->getContract()) {
                                /**
                                 * @var ReferralCreditsScheme
                                 */
                                $referralScheme = clone $referralSchemes[0];
                                $referralScheme->setIsBasedOn($referralSchemes[0]);

                                $this->entityManager->persist($referralScheme);

                                $referralAmount = $referralScheme->getReferralAmount()->getValue();

                                if (null !== $referralAmount) {
                                    $earnCreditsAction = new EarnContractCreditsAction();
                                    $earnCreditsAction->setAmount($referralAmount);
                                    $earnCreditsAction->setEndTime(new \DateTime());
                                    $earnCreditsAction->setStartTime(new \DateTime());
                                    $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                                    $earnCreditsAction->setObject($contract);
                                    $earnCreditsAction->setScheme($referralScheme);
                                    $earnCreditsAction->setInstrument($completedApplication->getContract());

                                    $this->commandBus->handle(new UpdateTransaction($earnCreditsAction));
                                    $this->commandBus->handle(new UpdatePointCreditsActions($contract, $earnCreditsAction));

                                    $this->entityManager->flush();

                                    $this->entityManager->persist($earnCreditsAction);
                                    $this->entityManager->persist($contract);

                                    $this->entityManager->flush();

                                    $io->text('Updated earnings.');
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $qb = $this->entityManager->getRepository(ApplicationRequest::class)->createQueryBuilder('app');
            $completedApplications = $qb->where($qb->expr()->eq('app.status', $qb->expr()->literal(ApplicationRequestStatus::COMPLETED)))
                ->andWhere($qb->expr()->eq('app.type', $qb->expr()->literal(ApplicationRequestType::CONTRACT_RENEWAL)))
                ->getQuery()
                ->getResult();

            $qb = $this->entityManager->getRepository(CreditsScheme::class)->createQueryBuilder('scheme');
            $renewalSchemes = $qb->where($qb->expr()->eq('scheme.schemeId', ':schemeId'))
                ->andWhere($qb->expr()->isNull('scheme.isBasedOn'))
                ->setParameter('schemeId', 'RN')
                ->getQuery()
                ->getResult();

            foreach ($completedApplications as $completedApplication) {
                if (null !== ($contract = $completedApplication->getContract())) {
                    $earned = false;
                    foreach ($contract->getPointCreditsActions() as $creditsAction) {
                        if ($creditsAction instanceof EarnContractCreditsAction &&
                            null !== $creditsAction->getScheme() &&
                            'RN' === $creditsAction->getScheme()->getSchemeId()
                        ) {
                            $earned = true;
                            break;
                        }
                    }

                    if (false === $earned && null !== $contract) {
                        $renewalScheme = clone $renewalSchemes[0];
                        $renewalScheme->setIsBasedOn($renewalSchemes[0]);

                        $amount = $renewalScheme->getAmount()->getValue();

                        if (null !== $amount) {
                            $this->entityManager->persist($renewalScheme);

                            $earnCreditsAction = new EarnContractCreditsAction();
                            $earnCreditsAction->setAmount($amount);
                            $earnCreditsAction->setEndTime(new \DateTime());
                            $earnCreditsAction->setStartTime(new \DateTime());
                            $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                            $earnCreditsAction->setObject($contract);
                            $earnCreditsAction->setScheme($renewalScheme);
                            $this->commandBus->handle(new UpdateTransaction($earnCreditsAction));
                            $this->commandBus->handle(new UpdatePointCreditsActions($contract, $earnCreditsAction));

                            $this->entityManager->persist($earnCreditsAction);
                            $this->entityManager->persist($contract);
                            $this->entityManager->flush();

                            $io->text('Updated earnings for #'.$contract->getContractNumber());
                        }
                    }
                }
            }
        }

        return 0;
    }
}
