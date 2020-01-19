<?php

declare(strict_types=1);

namespace App\Model;

use App\Domain\Command\CreditsTransaction\UpdateCreditsTransactionNumber;
use App\Entity\CreditsTransaction;
use App\Entity\MonetaryAmount;
use App\Entity\MoneyCreditsTransaction;
use App\Entity\PointCreditsTransaction;
use App\Entity\QuantitativeValue;
use App\Entity\UpdateCreditsAction;
use App\Entity\WithdrawCreditsAction;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;

class CreditsTransactionProcessor
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
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
    }

    /**
     * @param UpdateCreditsAction $updateCreditsAction
     *
     * @return CreditsTransaction
     */
    public function createCreditsTransaction(UpdateCreditsAction $updateCreditsAction)
    {
        $amount = null;
        $creditsTransaction = null;

        if (null !== $updateCreditsAction->getCurrency()) {
            $creditsTransaction = new MoneyCreditsTransaction();
            $amount = new MonetaryAmount(null !== $updateCreditsAction->getAmount() ? $updateCreditsAction->getAmount() : '0', $updateCreditsAction->getCurrency());
            $creditsTransaction->setAmount($amount);
        } else {
            $creditsTransaction = new PointCreditsTransaction();
            $amount = new QuantitativeValue($updateCreditsAction->getAmount());
            $creditsTransaction->setAmount($amount);
        }

        if ($creditsTransaction instanceof MoneyCreditsTransaction && $updateCreditsAction instanceof WithdrawCreditsAction) {
            $creditsTransaction->addPayment($updateCreditsAction->getInstrument());
        }

        $this->commandBus->handle(new UpdateCreditsTransactionNumber($creditsTransaction));
        $this->entityManager->persist($creditsTransaction);

        return $creditsTransaction;
    }
}
