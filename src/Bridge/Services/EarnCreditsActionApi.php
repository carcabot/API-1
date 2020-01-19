<?php

declare(strict_types=1);

namespace App\Bridge\Services;

use App\Document\PointHistory;
use App\Domain\Command\Contract\UpdatePointCreditsActions;
use App\Domain\Command\UpdateCreditsAction\UpdateTransaction;
use App\Entity\Contract;
use App\Entity\CreditsScheme;
use App\Entity\EarnContractCreditsAction;
use App\Entity\ReferralCreditsScheme;
use App\Enum\ActionStatus;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;

class EarnCreditsActionApi
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CommandBus             $commandBus
     * @param DocumentManager        $documentManager
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(CommandBus $commandBus, DocumentManager $documentManager, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->commandBus = $commandBus;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function createEarnCreditsActions(array $earnCreditsActions)
    {
        $batchSize = 20;
        foreach ($earnCreditsActions as $earnCreditsAction) {
            $this->createEarnCreditsAction($earnCreditsAction);
            $this->entityManager->flush();
            --$batchSize;
            if (0 === $batchSize) {
                $batchSize = 20;
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function createEarnCreditsAction(PointHistory $earnCreditsActionData): EarnContractCreditsAction
    {
        $earnCreditsAction = new EarnContractCreditsAction();

        if (null !== $earnCreditsActionData->getPointId() && 'RED' !== $earnCreditsActionData->getPointId()) {
            $contract = $this->entityManager->getRepository(Contract::class)->findOneBy(['contractNumber' => $earnCreditsActionData->getContract()]);

            if (null !== $contract) {
                $existingCreditScheme = $this->entityManager->getRepository(CreditsScheme::class)->findOneBy(['schemeId' => $earnCreditsActionData->getPointId()]);

                if ('RF' === $earnCreditsActionData->getPointId()) {
                    $existingCreditScheme = $this->entityManager->getRepository(ReferralCreditsScheme::class)->findOneBy(['schemeId' => $earnCreditsActionData->getPointId()]);
                }

                if (null !== $existingCreditScheme) {
                    $creditScheme = clone $existingCreditScheme;
                    $creditScheme->setIsBasedOn($existingCreditScheme);
                    $earnCreditsAction->setObject($contract);
                    $earnCreditsAction->setScheme($creditScheme);
                    $earnCreditsAction->setSender($contract->getCustomer());

                    if (null !== $earnCreditsActionData->getDate()) {
                        $earnCreditsAction->setStartTime($earnCreditsActionData->getDate());
                    }

                    $earnCreditsAction->setAmount((string) $earnCreditsActionData->getPoints());
                    $earnCreditsAction->setStatus(new ActionStatus(ActionStatus::COMPLETED));

                    $this->commandBus->handle(new UpdateTransaction($earnCreditsAction));
                    $this->commandBus->handle(new UpdatePointCreditsActions($contract, $earnCreditsAction));

                    $this->entityManager->persist($creditScheme);
                    $this->entityManager->persist($contract);
                    $this->entityManager->persist($earnCreditsAction);
                }
            }
        }

        return $earnCreditsAction;
    }
}
