<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Entity\AffiliateProgram;
use App\Entity\AffiliateProgramCommissionConfiguration;
use App\Entity\AffiliateProgramTransaction;
use App\Entity\CustomerAccount;
use App\Entity\EarnContractAffiliateCreditsAction;
use App\Entity\EarnCustomerAffiliateCreditsAction;
use App\Entity\MonetaryAmount;
use App\Entity\MoneyCreditsTransaction;
use App\Entity\PointCreditsExchangeRate;
use App\Entity\PointCreditsTransaction;
use App\Entity\QuantitativeValue;
use App\Entity\UpdateCreditsAction;
use App\Enum\ActionStatus;
use App\Enum\AffiliateCommissionStatus;
use App\Enum\AffiliateWebServicePartner;
use App\Enum\CommissionAllocation;
use App\Enum\CommissionType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Gedmo\Blameable\BlameableListener;
use Symfony\Component\Serializer\SerializerInterface;

class AffiliateProgramCommissionConversionCalculator
{
    /**
     * @var BlameableListener
     */
    private $blameableListener;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param BlameableListener      $blameableListener
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param SerializerInterface    $serializer
     * @param string                 $timezone
     */
    public function __construct(BlameableListener $blameableListener, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, SerializerInterface $serializer, string $timezone)
    {
        $this->blameableListener = $blameableListener;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->serializer = $serializer;
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function processData(array $data, string $provider)
    {
        if (\count($data) > 0) {
            $now = new \DateTime();
            $pointExchangeRate = null;
            $moneyCommissionConfiguration = null;
            $pointsCommissionConfiguration = null;

            $qb = $this->entityManager->getRepository(AffiliateProgramCommissionConfiguration::class)->createQueryBuilder('config');
            $expr = $qb->expr();

            $commissionConfigurations = $qb->where($expr->andX(
                    $expr->orX(
                        $expr->eq('config.allocationType', ':moneyType'),
                        $expr->eq('config.allocationType', ':pointType')
                    ),
                    $expr->orX(
                        $expr->isNull('config.provider'),
                        $expr->eq('config.provider', ':provider')
                    )
                ))
                ->andWhere($expr->isNull('config.isBasedOn'))
                ->setParameter('moneyType', new CommissionAllocation(CommissionAllocation::MONEY))
                ->setParameter('pointType', new CommissionAllocation(CommissionAllocation::POINTS))
                ->setParameter('provider', new AffiliateWebServicePartner($provider))
                ->orderBy('config.dateCreated', 'DESC')
                ->getQuery()
                ->getResult();

            $moneyType = false;
            $pointsType = false;
            foreach ($commissionConfigurations as $commissionConfiguration) {
                if (false === $moneyType && CommissionAllocation::MONEY === $commissionConfiguration->getAllocationType()->getValue()) {
                    if (null !== $commissionConfiguration->getProvider()) {
                        $moneyCommissionConfiguration = $commissionConfiguration;
                        $moneyType = true;
                        continue;
                    }

                    if (null === $moneyCommissionConfiguration) {
                        $moneyCommissionConfiguration = $commissionConfiguration;
                    }
                }

                if (false === $pointsType && CommissionAllocation::POINTS === $commissionConfiguration->getAllocationType()->getValue()) {
                    if (null !== $commissionConfiguration->getProvider()) {
                        $pointsCommissionConfiguration = $commissionConfiguration;
                        $pointsType = true;
                    }

                    if (null === $pointsCommissionConfiguration) {
                        $pointsCommissionConfiguration = $commissionConfiguration;
                    }
                }
            }

            $clonedMoneyConfig = null;
            if (null !== $moneyCommissionConfiguration) {
                $clonedMoneyConfig = clone $moneyCommissionConfiguration;
                $clonedMoneyConfig->setIsBasedOn($moneyCommissionConfiguration);
                $this->entityManager->persist($clonedMoneyConfig);
            }

            $clonedPointsConfig = null;
            if (null !== $pointsCommissionConfiguration) {
                $clonedPointsConfig = clone $pointsCommissionConfiguration;
                $clonedPointsConfig->setIsBasedOn($pointsCommissionConfiguration);
                $this->entityManager->persist($clonedPointsConfig);
            }

            $pointCreditsExchangeRateQb = $this->entityManager->getRepository(PointCreditsExchangeRate::class)->createQueryBuilder('exchangeRate');
            $expr = $pointCreditsExchangeRateQb->expr();

            $pointCreditsExchangeRates = $pointCreditsExchangeRateQb->where(
                $expr->andX(
                    $expr->orX(
                        $expr->isNull('exchangeRate.validFrom'),
                        $expr->lte('exchangeRate.validFrom', ':now')
                    ),
                    $expr->orX(
                        $expr->isNull('exchangeRate.validThrough'),
                        $expr->gte('exchangeRate.validThrough', ':now')
                    )
                ))
                ->andWhere($expr->isNull('exchangeRate.isBasedOn'))
                ->setParameter('now', $now)
                ->getQuery()
                ->getResult();

            foreach ($pointCreditsExchangeRates as $pointCreditsExchangeRate) {
                // highest priority
                if (null !== $pointCreditsExchangeRate->getValidFrom() && null !== $pointCreditsExchangeRate->getValidThrough()) {
                    $pointExchangeRate = $pointCreditsExchangeRate;
                    break;
                }

                if (
                    (null === $pointCreditsExchangeRate->getValidFrom() && null !== $pointCreditsExchangeRate->getValidThrough()) ||
                    (null !== $pointCreditsExchangeRate->getValidFrom() && null === $pointCreditsExchangeRate->getValidThrough())
                ) {
                    $pointExchangeRate = $pointCreditsExchangeRate;
                }

                // lowest priority
                if (null === $pointExchangeRate && null === $pointCreditsExchangeRate->getValidFrom() && null === $pointCreditsExchangeRate->getValidThrough()) {
                    $pointExchangeRate = $pointCreditsExchangeRate;
                }
            }

            $exchangeRate = 1;
            $clonedPointExchangeRate = null;
            if (null !== $pointExchangeRate) {
                $clonedPointExchangeRate = clone $pointExchangeRate;
                $clonedPointExchangeRate->setIsBasedOn($pointExchangeRate);
                $this->entityManager->persist($clonedPointExchangeRate);

                $exchangeRate = $clonedPointExchangeRate->getValue() / $clonedPointExchangeRate->getBaseAmount()->getValue();
            }

            $affiliateProgramIdMap = [];
            $affiliateProgramMap = [];
            $creditsGroups = [];
            $customerIdMap = [];
            $customerMap = [];
            $transactionIdMap = [];
            $transactionMap = [];

            foreach ($data as $key => $datum) {
                $affiliateProgramIdMap[$key] = $datum['affiliateProgram']['programNumber'];
                $customerIdMap[$key] = $datum['customer']['accountNumber'];
                $transactionIdMap[$key] = $datum['transactionNumber'];

                if (!empty($datum['groupId'])) {
                    if (isset($creditsGroups[$datum['groupId']])) {
                        $creditsGroups[$datum['groupId']]['transactionNumbers'][] = $datum['transactionNumber'];
                    } else {
                        $creditsGroups[$datum['groupId']] = [
                            'moneyCreditsAction' => null,
                            'pointCreditsAction' => null,
                            'transactionNumbers' => [$datum['transactionNumber']],
                        ];
                    }

                    unset($data[$key]['groupId']);
                }

                unset($data[$key]['affiliateProgram']);
                unset($data[$key]['customer']);
            }

            $affiliatePrograms = $this->entityManager->getRepository(AffiliateProgram::class)->findBy(['programNumber' => \array_unique(\array_values($affiliateProgramIdMap))]);

            foreach ($affiliatePrograms as $affiliateProgram) {
                foreach ($affiliateProgramIdMap as $key => $affiliateProgramNumber) {
                    if ($affiliateProgramNumber === $affiliateProgram->getProgramNumber()) {
                        $affiliateProgramMap[$key] = $affiliateProgram;
                    }
                }
            }

            $customers = $this->entityManager->getRepository(CustomerAccount::class)->findBy(['accountNumber' => \array_unique(\array_values($customerIdMap))]);

            foreach ($customers as $customer) {
                foreach ($customerIdMap as $key => $accountNumber) {
                    if ($accountNumber === $customer->getAccountNumber()) {
                        $customerMap[$key] = $customer;
                    }
                }
            }

            $transactions = $this->entityManager->getRepository(AffiliateProgramTransaction::class)->findBy([
                'provider' => new AffiliateWebServicePartner($provider),
                'transactionNumber' => \array_unique(\array_values($transactionIdMap)),
            ]);

            foreach ($transactions as $transaction) {
                foreach ($transactionIdMap as $key => $transactionNumber) {
                    if ($transactionNumber === $transaction->getTransactionNumber()) {
                        $transactionMap[$key] = $transaction;
                    }
                }
            }

            foreach ($data as $key => $datum) {
                if (null !== $clonedMoneyConfig) {
                    $datum['moneyCreditsAmount'] = [
                        'currency' => 'SGD',
                        'value' => $this->calculateCommission($datum['commissionAmount']['value'], $clonedMoneyConfig),
                    ];
                }

                if (null !== $clonedPointsConfig) {
                    $points = $this->calculateCommission($datum['commissionAmount']['value'], $clonedPointsConfig) * $exchangeRate;
                    $datum['pointCreditsAmount'] = [
                        'value' => $points,
                    ];
                }

                if (!empty($transactionMap[$key])) {
                    // if existing transaction and no change to status
                    $transaction = $transactionMap[$key];
                    if ($transaction->getCommissionStatus()->getValue() === $datum['commissionStatus']->getValue()) {
                        continue;
                    }

                    $datum['@id'] = $this->iriConverter->getIriFromItem($transaction);
                }

                $transaction = $this->serializer->deserialize(\json_encode($datum), AffiliateProgramTransaction::class, 'jsonld', ['affiliate_program_transaction_write']);

                if ($transaction instanceof AffiliateProgramTransaction) {
                    if (isset($customerMap[$key])) {
                        $transaction->setCustomer($customerMap[$key]);
                    }

                    if (isset($affiliateProgramMap[$key])) {
                        $transaction->setAffiliateProgram($affiliateProgramMap[$key]);
                    }

                    if (null !== $clonedMoneyConfig) {
                        $transaction->setMoneyCommissionConfiguration($clonedMoneyConfig);
                    }

                    if (null !== $clonedPointsConfig) {
                        $transaction->setPointCommissionConfiguration($clonedPointsConfig);
                    }

                    if (null !== $clonedPointExchangeRate) {
                        $transaction->setPointCreditsExchangeRate($clonedPointExchangeRate);
                    }

                    $creditsGroups = $this->createCustomerCredits($transaction, $creditsGroups);
                    $this->entityManager->persist($transaction);
                    $this->entityManager->flush();
                }
            }
        }
    }

    // @todo refactor, command bus?
    private function createCustomerCredits(AffiliateProgramTransaction $transaction, array $creditsGroups)
    {
        $now = new \DateTime();
        $customer = $transaction->getCustomer();
        $contract = null;
        $groupId = null;
        $awardCredit = false;

        if (null !== $customer) {
            $contract = $customer->getDefaultCreditsContract();
        }

        foreach ($creditsGroups as $key => $creditsGroup) {
            if (\in_array($transaction->getTransactionNumber(), $creditsGroup['transactionNumbers'], true)) {
                $groupId = $key;
            }
        }

        if (AffiliateCommissionStatus::PENDING === $transaction->getCommissionStatus()->getValue()) {
            $actionStatus = new ActionStatus(ActionStatus::IN_PROGRESS);
        } else {
            if (AffiliateCommissionStatus::APPROVED === $transaction->getCommissionStatus()->getValue()) {
                $awardCredit = true;
            }
            $actionStatus = new ActionStatus(ActionStatus::COMPLETED);
        }

        if (null !== $customer) {
            if (null !== $transaction->getPointCreditsAmount()->getValue()) {
                $transactionAmount = $transaction->getPointCreditsAmount()->getValue();
                $pointCreditsAction = null;

                // if existing transaction, must find the existing action
                if (null !== $transaction->getId()) {
                    $pointCreditsAction = $this->findExistingCreditsAction($transaction);
                }

                if (null !== $groupId) {
                    if (null !== $pointCreditsAction) {
                        $creditsGroups[$groupId]['pointCreditsAction'] = $pointCreditsAction;
                    } elseif (null !== $creditsGroups[$groupId]['pointCreditsAction']) {
                        $pointCreditsAction = $creditsGroups[$groupId]['pointCreditsAction'];
                        $pointCreditsAction->setAmount((string) ($pointCreditsAction->getAmount() + $transactionAmount));
                        $pointCreditsAction->addTransaction($transaction);
                    }
                }

                if (null === $pointCreditsAction) {
                    // not grouped, check if points to go to contract automagically
                    if (null !== $contract) {
                        $pointCreditsAction = new EarnContractAffiliateCreditsAction();
                        $pointCreditsAction->setObject($contract);
                    } else {
                        $pointCreditsAction = new EarnCustomerAffiliateCreditsAction();
                        $pointCreditsAction->setObject($customer);
                    }

                    $pointCreditsAction->setAmount($transactionAmount);
                    $pointCreditsAction->addTransaction($transaction);
                    $pointCreditsAction->setStartTime($now);
                    // add to customer
                    $customer->addPointCreditsAction($pointCreditsAction);

                    $creditsTransaction = new PointCreditsTransaction();

                    $pointCreditsAction->setCreditsTransaction($creditsTransaction);

                    if (null !== $groupId) {
                        $creditsGroups[$groupId]['pointCreditsAction'] = $pointCreditsAction;
                    }
                } else {
                    $creditsTransaction = $pointCreditsAction->getCreditsTransaction();
                }

                // only update transaction if it has been approved
                if (true === $awardCredit) {
                    $creditsAmount = $creditsTransaction->getAmount()->getValue() + $transactionAmount;
                    $creditsTransaction->setAmount(new QuantitativeValue((string) $creditsAmount));
                }

                $pointCreditsAction->setStatus($actionStatus);
                $this->entityManager->persist($creditsTransaction);
                $this->entityManager->persist($pointCreditsAction);
            }

            if (null !== $transaction->getMoneyCreditsAmount()->getValue()) {
                $transactionAmount = $transaction->getMoneyCreditsAmount()->getValue();
                $moneyCreditsAction = null;

                // if existing transaction, must find the existing action
                if (null !== $transaction->getId()) {
                    $moneyCreditsAction = $this->findExistingCreditsAction($transaction, $transaction->getMoneyCreditsAmount()->getCurrency());
                }

                if (null !== $groupId) {
                    if (null !== $moneyCreditsAction) {
                        $creditsGroups[$groupId]['moneyCreditsAction'] = $moneyCreditsAction;
                    } elseif (null !== $creditsGroups[$groupId]['moneyCreditsAction']) {
                        $moneyCreditsAction = $creditsGroups[$groupId]['moneyCreditsAction'];
                        $moneyCreditsAction->setAmount((string) ($moneyCreditsAction->getAmount() + $transactionAmount));
                        $moneyCreditsAction->addTransaction($transaction);
                    }
                }

                if (null === $moneyCreditsAction) {
                    $moneyCreditsAction = new EarnCustomerAffiliateCreditsAction();
                    $moneyCreditsAction->setObject($customer);
                    $moneyCreditsAction->setStartTime($now);
                    $moneyCreditsAction->setAmount($transactionAmount);
                    $moneyCreditsAction->setCurrency($transaction->getMoneyCreditsAmount()->getCurrency());
                    $moneyCreditsAction->addTransaction($transaction);
                    // add to customer
                    $customer->addMoneyCreditsAction($moneyCreditsAction);

                    $creditsTransaction = new MoneyCreditsTransaction();
                    // set to action
                    $moneyCreditsAction->setCreditsTransaction($creditsTransaction);

                    if (null !== $groupId) {
                        $creditsGroups[$groupId]['moneyCreditsAction'] = $moneyCreditsAction;
                    }
                } else {
                    $creditsTransaction = $moneyCreditsAction->getCreditsTransaction();
                }

                // only update transaction if it has been approved
                if (true === $awardCredit) {
                    $creditsAmount = $creditsTransaction->getAmount()->getValue() + $transactionAmount;
                    $creditsTransaction->setAmount(new MonetaryAmount((string) $creditsAmount, $transaction->getMoneyCreditsAmount()->getCurrency()));
                }

                $moneyCreditsAction->setStatus($actionStatus);
                $this->entityManager->persist($creditsTransaction);
                $this->entityManager->persist($moneyCreditsAction);
            }
        }

        return $creditsGroups;
    }

    private function calculateCommission(string $commission, AffiliateProgramCommissionConfiguration $commissionConfiguration): string
    {
        $commissionValue = 0;

        if (CommissionType::FIXED_RATE === $commissionConfiguration->getType()->getValue()) {
            $commissionValue = $commissionConfiguration->getValue();
        } else {
            $commissionMultiplier = $commissionConfiguration->getValue();

            if ($commissionMultiplier > 1) {
                $commissionMultiplier = $commissionMultiplier / 100;
            }

            $commissionValue = $commission * $commissionMultiplier;
        }

        return (string) \round($commissionValue, 2);
    }

    private function findExistingCreditsAction(AffiliateProgramTransaction $transaction, string $currency = null)
    {
        $creditsActionQb = $this->entityManager->getRepository(UpdateCreditsAction::class)->createQueryBuilder('creditsAction');
        $expr = $creditsActionQb->expr();

        $creditsActionQb
            ->leftJoin(
                EarnContractAffiliateCreditsAction::class,
                'contractAffiliateCredits',
                Expr\Join::WITH,
                'creditsAction.id = contractAffiliateCredits.id'
            )
            ->leftJoin(
                EarnCustomerAffiliateCreditsAction::class,
                'customerAffiliateCredits',
                Expr\Join::WITH,
                'creditsAction.id = customerAffiliateCredits.id'
            )
            ->leftJoin('contractAffiliateCredits.transactions', 'contractTransactions')
            ->leftJoin('customerAffiliateCredits.transactions', 'customerTransactions')
            ->where($expr->orX(
                $expr->eq('contractTransactions.id', ':transactionId'),
                $expr->eq('customerTransactions.id', ':transactionId')
            ))
            ->setParameter('transactionId', $transaction->getId());

        if (null !== $currency) {
            $creditsActionQb->andWhere($expr->eq('creditsAction.currency', ':currency'))
                ->setParameter('currency', $currency);
        } else {
            $creditsActionQb->andWhere($expr->isNull('creditsAction.currency'));
        }

        $creditsActions = $creditsActionQb->getQuery()->getResult();

        if (\count($creditsActions) > 0) {
            return \current($creditsActions);
        }

        return null;
    }
}
