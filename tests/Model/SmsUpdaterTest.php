<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 25/4/19
 * Time: 3:38 PM.
 */

namespace App\Tests\Model;

use App\Entity\ActivitySmsHistory;
use App\Entity\SmsActivity;
use App\Entity\SmsHistory;
use App\Model\SmsUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class SmsUpdaterTest extends TestCase
{
    public function testSmsUpdaterCreateFunction()
    {
        $data = [];

        $smsHistoryProphecy = $this->prophesize(SmsHistory::class);
        $smsHistory = $smsHistoryProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterfaceProphecy->deserialize(\json_encode($data), SmsHistory::class, 'jsonld', ['sms_history_write'])->willReturn($smsHistory);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($smsHistory)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $smsUpdated = new SmsUpdater($entityManager, $serializerInterface);
        $actualSmsHistory = $smsUpdated->create($data);

        $this->assertEquals($smsHistory, $actualSmsHistory);
    }

    public function testSmsUpdaterCreateActivitySmsHistory()
    {
        $smsActivityProphecy = $this->prophesize(SmsActivity::class);
        $smsActivity = $smsActivityProphecy->reveal();

        $smsHistoryProphecy = $this->prophesize(SmsHistory::class);
        $smsHistory = $smsHistoryProphecy->reveal();

        $activityHistory = new ActivitySmsHistory();
        $activityHistory->setActivity($smsActivity);
        $activityHistory->setOutboundSMS($smsHistory);

        $entityManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $entityManagerProphecy->persist($activityHistory)->shouldBeCalled();
        $entityManagerProphecy->flush()->shouldBeCalled();
        $entityManager = $entityManagerProphecy->reveal();

        $serializerInterfaceProphecy = $this->prophesize(SerializerInterface::class);
        $serializerInterface = $serializerInterfaceProphecy->reveal();

        $smsUpdater = new SmsUpdater($entityManager, $serializerInterface);
        $smsUpdater->createActivitySmsHistory($smsActivity, $smsHistory);
    }
}
