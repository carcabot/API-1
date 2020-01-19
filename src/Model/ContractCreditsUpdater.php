<?php

declare(strict_types=1);

namespace App\Model;

use App\Domain\Command\Contract\UpdatePointCreditsActions;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\Contract;
use App\Entity\CreditsScheme;
use App\Entity\EarnContractCreditsAction;
use App\Entity\ExpireContractCreditsAction;
use App\Enum\ActionStatus;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;

class ContractCreditsUpdater
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     * @param string                 $documentConverterHost
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, LoggerInterface $logger, string $documentConverterHost)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->documentConverterHost = $documentConverterHost;
    }

    public function processArrayData(array $data, string $providerName = '')
    {
        foreach ($data as $datum) {
            $this->updateCustomerPoints($datum);
        }
        $this->entityManager->flush();
    }

    protected function updateCustomerPoints(array $data)
    {
        $customerAccount = null;
        $creditsScheme = null;
        try {
            if (isset($data['contractNumber'])) {
                $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $data['contractNumber']]);

                if (null !== $contract) {
                    if (isset($data['schemeId']) && isset($data['date'])) {
                        if ('GI' === $data['schemeId']) {
                            if (true === $this->hasEarnedGIROPoints($contract)) {
                                return;
                            }
                        }
                        $date = new \DateTime($data['date']);

                        $qb = $this->entityManager->getRepository(CreditsScheme::class)->createQueryBuilder('scheme');
                        $expr = $qb->expr();
                        /**
                         * @var CreditsScheme[]
                         */
                        $creditsSchemes = $qb->where($expr->eq('scheme.schemeId', ':schemeId'))
                            ->andWhere($expr->lte('scheme.validFrom', ':date'))
                            ->andWhere($expr->orX($expr->gte('scheme.validThrough', ':date'), $expr->isNull('scheme.validThrough')))
                            ->setParameter('schemeId', $data['schemeId'])
                            ->setParameter('date', $date)
                            ->orderBy('scheme.dateModified', 'DESC')
                            ->getQuery()
                            ->getResult();

                        foreach ($creditsSchemes as $creditsScheme) {
                            $amount = $creditsScheme->getAmount()->getValue();

                            if (null !== $amount) {
                                $scheme = clone $creditsScheme;
                                $scheme->setIsBasedOn($creditsScheme);
                                $this->entityManager->persist($scheme);

                                $earnCreditsAction = new EarnContractCreditsAction();
                                $earnCreditsAction->setAmount($amount);
                                $earnCreditsAction->setEndTime(new \DateTime());
                                $earnCreditsAction->setStartTime(new \DateTime());
                                $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                                $earnCreditsAction->setObject($contract);
                                $earnCreditsAction->setScheme($scheme);
                                $this->commandBus->handle(new UpdateTransaction($earnCreditsAction));
                                $this->commandBus->handle(new UpdatePointCreditsActions($contract, $earnCreditsAction));

                                $this->entityManager->persist($earnCreditsAction);
                                $this->entityManager->persist($contract);
                                $this->entityManager->flush();

                                /*if (null !== $scheme->getValidPeriod()->getValue() && null !== $scheme->getValidPeriod()->getUnitCode()) {
                                    $startDate = new \DateTime();
                                    if ('HUR' === $scheme->getValidPeriod()->getUnitCode()) {
                                        $startDate->modify('+'.$scheme->getValidPeriod()->getValue().' hour');
                                    } elseif ('DAY' === $scheme->getValidPeriod()->getUnitCode()) {
                                        $startDate->modify('+'.$scheme->getValidPeriod()->getValue().' day');
                                    } elseif ('MON' === $scheme->getValidPeriod()->getUnitCode()) {
                                        $startDate->modify('+'.$scheme->getValidPeriod()->getValue().' month');
                                    } elseif ('ANN' === $scheme->getValidPeriod()->getUnitCode()) {
                                        $startDate->modify('+'.$scheme->getValidPeriod()->getValue().' year');
                                    }

                                    $expireContractCreditAction = new ExpireContractCreditsAction();
                                    $expireContractCreditAction->setObject($contract);
                                    $expireContractCreditAction->setScheme($scheme);
                                    $expireContractCreditAction->setStartTime($startDate);
                                    $expireContractCreditAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));
                                    $expireContractCreditAction->setAmountUsed($amount);

                                    $this->commandBus->handle(new UpdateTransaction($expireContractCreditAction));
                                    $this->commandBus->handle(new UpdatePointCreditsActions($contract, $expireContractCreditAction));

                                    $this->entityManager->persist($expireContractCreditAction);
                                    $this->entityManager->flush();
                                }*/

                                break;
                            }
                        }
                    } else {
                        $this->logger->error('Scheme ID and date not specified.');
                    }
                } else {
                    $this->logger->error(\sprintf('Contract %s does not exist.', $data['contractNumber']));
                }
            } else {
                $this->logger->error('Contract not specified.');
            }
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
        }
    }

    protected function hasEarnedGIROPoints(Contract $contract)
    {
        $earnPointsCreditsActions = $contract->getPointCreditsActions();

        foreach ($earnPointsCreditsActions as $earnPointsCreditsAction) {
            if ($earnPointsCreditsAction instanceof EarnContractCreditsAction) {
                $creditsScheme = $earnPointsCreditsAction->getScheme();

                if (null !== $creditsScheme && 'GI' === $creditsScheme->getSchemeId()) {
                    return true;
                }
            }
        }

        return false;
    }
}
