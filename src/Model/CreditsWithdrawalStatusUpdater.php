<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\CreditsTransaction;
use App\Entity\WithdrawCreditsAction;
use App\Enum\ActionStatus;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;

class CreditsWithdrawalStatusUpdater
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function processArrayData(array $data)
    {
        foreach ($data as $datum) {
            $this->updateStatus($datum);
        }
        $this->entityManager->flush();
    }

    protected function updateStatus(array $data)
    {
        $creditsTransaction = $this->entityManager->getRepository(CreditsTransaction::class)->findOneBy([
            'creditsTransactionNumber' => $data['creditsTransaction']['creditsTransactionNumber'],
        ]);

        if (null !== $creditsTransaction) {
            $creditsAction = $this->entityManager->getRepository(WithdrawCreditsAction::class)->findOneBy([
                'creditsTransaction' => $creditsTransaction->getId(),
            ]);

            if (null !== $creditsAction) {
                $creditsAction->setStatus(new ActionStatus($data['payment']['status']));

                $this->entityManager->persist($creditsAction);

                $payment = $creditsAction->getInstrument();
                $payment->setStatus(new PaymentStatus($data['payment']['status']));

                if (!empty($data['payment']['returnMessage'])) {
                    $payment->setReturnMessage($data['payment']['returnMessage']);
                }
                $this->entityManager->persist($payment);
            }
        }
    }
}
