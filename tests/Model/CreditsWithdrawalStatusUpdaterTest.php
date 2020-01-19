<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 22/4/19
 * Time: 6:47 PM.
 */

namespace App\Tests\Model;

use App\Entity\CreditsTransaction;
use App\Entity\Payment;
use App\Entity\WithdrawCreditsAction;
use App\Enum\ActionStatus;
use App\Enum\PaymentStatus;
use App\Model\CreditsWithdrawalStatusUpdater;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreditsWithdrawalStatusUpdaterTest extends TestCase
{
    public function testProcessArrayDataFunctionWithCreditsTransaction()
    {
        $data = [];
        $data['creditsTransaction']['creditsTransactionNumber'] = '123456';
        $data['payment']['status'] = 'COMPLETED';
        $data['payment']['returnMessage'] = 'Test Message';

        $creditsTransactionProphecy = $this->prophesize(CreditsTransaction::class);
        $creditsTransactionProphecy->getId()->willReturn(123);
        $creditsTransaction = $creditsTransactionProphecy->reveal();

        $creditsTransactionRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $creditsTransactionRepositoryProphecy->findOneBy(['creditsTransactionNumber' => '123456'])->willReturn($creditsTransaction);
        $creditsTransactionRepository = $creditsTransactionRepositoryProphecy->reveal();

        $paymentProphecy = $this->prophesize(Payment::class);
        $paymentProphecy->setStatus(new PaymentStatus('COMPLETED'))->shouldBeCalled();
        $paymentProphecy->setReturnMessage('Test Message')->shouldBeCalled();
        $payment = $paymentProphecy->reveal();

        $withdrawCreditsActionProphecy = $this->prophesize(WithdrawCreditsAction::class);
        $withdrawCreditsActionProphecy->getInstrument()->willReturn($payment);
        $withdrawCreditsActionProphecy->setStatus(new ActionStatus('COMPLETED'))->shouldBeCalled();
        $withdrawCreditsAction = $withdrawCreditsActionProphecy->reveal();

        $withdrawCreditsActionRepositoryProphecy = $this->prophesize(ObjectRepository::class);
        $withdrawCreditsActionRepositoryProphecy->findOneBy(['creditsTransaction' => 123])->willReturn($withdrawCreditsAction);
        $withdrawCreditsActionRepository = $withdrawCreditsActionRepositoryProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->getRepository(CreditsTransaction::class)->willReturn($creditsTransactionRepository);
        $entityManagerProphecy->getRepository(WithdrawCreditsAction::class)->willReturn($withdrawCreditsActionRepository);
        $entityManagerProphecy->persist($withdrawCreditsAction)->shouldBeCalled();
        $entityManagerProphecy->persist($payment)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $creditsWithdrawalStatusUpdater = new CreditsWithdrawalStatusUpdater($entityManager);
        $creditsWithdrawalStatusUpdater->processArrayData([$data]);
    }
}
