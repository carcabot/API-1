<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Command\Contract\UpdatePointCreditsActions;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\Contract;
use App\Entity\EarnContractCreditsAction;
use App\Entity\ExpireContractCreditsAction;
use App\Entity\ReferralCreditsScheme;
use App\Enum\ActionStatus;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PatchReferralPoints extends Command
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
            ->setName('app:patch:referral-points')
            ->setDescription('Patch referral points.')
            ->addOption('object', null, InputOption::VALUE_OPTIONAL, 'For which specific contract', null)
            ->addOption('instrument', null, InputOption::VALUE_OPTIONAL, 'The activated contract using object\'s customer referral code', null)
            ->setHelp(<<<'EOF'
The %command.name% command patch referral points.
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

        $objectContractNumber = $input->getOption('object');
        $instrumentContractNumber = $input->getOption('instrument');

        $io->text('Start patching data');

        if (null !== $objectContractNumber) {
            $object = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $objectContractNumber]);

            if (null !== $object) {
                $instrument = null;
                if (null !== $instrumentContractNumber) {
                    $instrument = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $instrumentContractNumber]);
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
                    /**
                     * @var ReferralCreditsScheme
                     */
                    $referralScheme = clone $referralSchemes[0];
                    $referralScheme->setIsBasedOn($referralSchemes[0]);

                    $this->entityManager->persist($referralScheme);

                    $referralAmount = $referralScheme->getReferralAmount()->getValue();
                    $refereeAmount = $referralScheme->getRefereeAmount()->getValue();

                    if (null !== $referralAmount) {
                        $earnCreditsAction = new EarnContractCreditsAction();
                        $earnCreditsAction->setAmount($referralAmount);
                        $earnCreditsAction->setEndTime(new \DateTime());
                        $earnCreditsAction->setStartTime(new \DateTime());
                        $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                        $earnCreditsAction->setObject($object);
                        $earnCreditsAction->setScheme($referralScheme);
                        $earnCreditsAction->setInstrument($instrument);

                        $this->commandBus->handle(new UpdateTransaction($earnCreditsAction));
                        $this->commandBus->handle(new UpdatePointCreditsActions($object, $earnCreditsAction));

                        $this->entityManager->flush();

                        if (null !== $referralScheme->getValidPeriod()->getValue() && null !== $referralScheme->getValidPeriod()->getUnitCode()) {
                            $startDate = new \DateTime();
                            if ('HUR' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' hour');
                            } elseif ('DAY' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' day');
                            } elseif ('MON' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' month');
                            } elseif ('ANN' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' year');
                            }

                            $expireReferralContractCreditAction = new ExpireContractCreditsAction();
                            $expireReferralContractCreditAction->setObject($object);
                            $expireReferralContractCreditAction->setScheme($referralScheme);
                            $expireReferralContractCreditAction->setStartTime($startDate);
                            $expireReferralContractCreditAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));
                            $expireReferralContractCreditAction->setAmountUsed('0');

                            $this->commandBus->handle(new UpdateTransaction($expireReferralContractCreditAction));
                            $this->commandBus->handle(new UpdatePointCreditsActions($object, $expireReferralContractCreditAction));

                            $this->entityManager->persist($expireReferralContractCreditAction);
                        }

                        $this->entityManager->persist($earnCreditsAction);
                        $this->entityManager->persist($object);

                        $this->entityManager->flush();
                    }

                    if (null !== $refereeAmount && null !== $instrument) {
                        $earnCreditsAction = new EarnContractCreditsAction();
                        $earnCreditsAction->setAmount($refereeAmount);
                        $earnCreditsAction->setEndTime(new \DateTime());
                        $earnCreditsAction->setStartTime(new \DateTime());
                        $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                        $earnCreditsAction->setObject($instrument);
                        $earnCreditsAction->setScheme($referralScheme);
                        $earnCreditsAction->setInstrument($object);

                        $this->commandBus->handle(new UpdateTransaction($earnCreditsAction));
                        $this->commandBus->handle(new UpdatePointCreditsActions($instrument, $earnCreditsAction));

                        $this->entityManager->flush();

                        if (null !== $referralScheme->getValidPeriod()->getValue() && null !== $referralScheme->getValidPeriod()->getUnitCode()) {
                            $startDate = new \DateTime();
                            if ('HUR' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' hour');
                            } elseif ('DAY' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' day');
                            } elseif ('MON' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' month');
                            } elseif ('ANN' === $referralScheme->getValidPeriod()->getUnitCode()) {
                                $startDate->modify('+'.$referralScheme->getValidPeriod()->getValue().' year');
                            }

                            $expireRefereeContractCreditAction = new ExpireContractCreditsAction();
                            $expireRefereeContractCreditAction->setObject($instrument);
                            $expireRefereeContractCreditAction->setScheme($referralScheme);
                            $expireRefereeContractCreditAction->setStartTime($startDate);
                            $expireRefereeContractCreditAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));
                            $expireRefereeContractCreditAction->setAmountUsed('0');

                            $this->commandBus->handle(new UpdateTransaction($expireRefereeContractCreditAction));
                            $this->commandBus->handle(new UpdatePointCreditsActions($instrument, $expireRefereeContractCreditAction));

                            $this->entityManager->persist($expireRefereeContractCreditAction);
                        }

                        $this->entityManager->persist($earnCreditsAction);
                        $this->entityManager->persist($instrument);

                        $this->entityManager->flush();
                    }
                }
            } else {
                $io->error('Contract not found');
            }
        } else {
            $io->error('Contract number is missing');
        }

        return 0;
    }
}
