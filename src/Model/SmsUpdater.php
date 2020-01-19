<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\ActivitySmsHistory;
use App\Entity\SmsActivity;
use App\Entity\SmsHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SmsUpdater
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    /**
     * Creates SmsHistory entity.
     *
     * @param array $data
     */
    public function create(array $data)
    {
        $smsHistory = $this->serializer->deserialize(\json_encode($data), SmsHistory::class, 'jsonld', ['sms_history_write']);

        $this->entityManager->persist($smsHistory);
        $this->entityManager->flush();

        return $smsHistory;
    }

    /**
     * Creates ActivitySmsHistory entity. Two way shiet.
     *
     * @param SmsActivity $activity
     * @param SmsHistory  $history
     */
    public function createActivitySmsHistory(SmsActivity $activity, SmsHistory $history)
    {
        $activityHistory = new ActivitySmsHistory();
        $activityHistory->setActivity($activity);
        $activityHistory->setOutboundSMS($history);

        $this->entityManager->persist($activityHistory);
        $this->entityManager->flush();
    }
}
