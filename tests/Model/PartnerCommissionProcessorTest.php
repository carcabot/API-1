<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 25/4/19
 * Time: 6:41 PM.
 */

namespace App\Tests\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Domain\Command\PartnerCommissionStatement\UpdateEndDate;
use App\Domain\Command\PartnerCommissionStatement\UpdateStatementNumber;
use App\Entity\Partner;
use App\Entity\PartnerCommissionStatement;
use App\Enum\CommissionStatementStatus;
use App\Enum\PaymentStatus;
use App\Model\PartnerCommissionProcessor;
use Disque\Queue\Queue;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class PartnerCommissionProcessorTest extends TestCase
{
    public function testCreateNewStatementWithoutJoiningDate()
    {
        $timeZone = new \DateTimeZone('Asia/Singapore');
        $payoutDate = new \DateTime('2019-05-01');

        $partnerProphecy = $this->prophesize(Partner::class);
        $partnerProphecy->getJoiningDate()->willReturn(new \DateTime('2019-04-01'));
        $partner = $partnerProphecy->reveal();

        $commissionStatement = new PartnerCommissionStatement();
        $commissionStatement->setPartner($partner);
        $commissionStatement->setPaymentStatus(new PaymentStatus(PaymentStatus::PENDING));
        $commissionStatement->setStatus(new CommissionStatementStatus(CommissionStatementStatus::NEW));
        $commissionStatement->setStartDate(new \DateTime('2019-04-01'));

        $commandBusProphecy = $this->prophesize(CommandBus::class);
        $commandBusProphecy->handle(new UpdateEndDate($commissionStatement, $payoutDate, $timeZone))->shouldBeCalled();
        $commandBusProphecy->handle(new UpdateStatementNumber($commissionStatement))->shouldBeCalled();
        $commandBus = $commandBusProphecy->reveal();

        $iriConverterInterfaceProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterInterface = $iriConverterInterfaceProphecy->reveal();

        $emailQueueProphecy = $this->prophesize(Queue::class);
        $emailQueue = $emailQueueProphecy->reveal();

        $loggerProphecy = $this->prophesize(Logger::class);
        $logger = $loggerProphecy->reveal();

        $reportQueueProphecy = $this->prophesize(Queue::class);
        $reportQueue = $reportQueueProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManager = $entityManagerProphecy->reveal();

        $partnerCommissionProcessor = new PartnerCommissionProcessor($commandBus, $entityManager, $iriConverterInterface, $emailQueue, $logger, $reportQueue, $serializerInterface, '', '', 'Asia/Singapore');

        $expectedCommissionStatement = $partnerCommissionProcessor->createNewStatement($partner, $payoutDate, null);

        $this->assertEquals($commissionStatement, $expectedCommissionStatement);
    }

    //@todo Write Test For CreateNewStatement Function With NEW DATETIME
}
