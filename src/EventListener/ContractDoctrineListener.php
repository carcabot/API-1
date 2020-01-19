<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Disque\JobType;
use App\Entity\Contract;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;

class ContractDoctrineListener
{
    /**
     * @var DisqueQueue
     */
    protected $contractsQueue;

    /**
     * @param DisqueQueue $contractsQueue
     */
    public function __construct(DisqueQueue $contractsQueue)
    {
        $this->contractsQueue = $contractsQueue;
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $queued = [];

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (Contract::class !== ClassUtils::getClass($entity)) {
                continue;
            }

            if (!\in_array($entity->getId(), $queued, true)) {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $entity->getId(),
                        'mode' => 'insert',
                    ],
                    'type' => JobType::CONTRACT_UPDATE_CACHE_TABLE,
                ]);

                $this->contractsQueue->push($job);
                $queued[] = $entity->getId();
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (Contract::class !== ClassUtils::getClass($entity)) {
                continue;
            }

            if (!\in_array($entity->getId(), $queued, true)) {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $entity->getId(),
                        'mode' => 'update',
                    ],
                    'type' => JobType::CONTRACT_UPDATE_CACHE_TABLE,
                ]);

                $this->contractsQueue->push($job);
                $queued[] = $entity->getId();
            }
        }

        foreach ($uow->getScheduledCollectionUpdates() as $entity) {
            if (Contract::class !== ClassUtils::getClass($entity)) {
                continue;
            }

            if (!\in_array($entity->getId(), $queued, true)) {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $entity->getId(),
                        'mode' => 'update',
                    ],
                    'type' => JobType::CONTRACT_UPDATE_CACHE_TABLE,
                ]);

                $this->contractsQueue->push($job);
                $queued[] = $entity->getId();
            }
        }

        foreach ($uow->getScheduledCollectionDeletions() as $entity) {
            if (Contract::class !== ClassUtils::getClass($entity)) {
                continue;
            }

            if (!\in_array($entity->getId(), $queued, true)) {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $entity->getId(),
                        'mode' => 'delete',
                    ],
                    'type' => JobType::CONTRACT_UPDATE_CACHE_TABLE,
                ]);

                $this->contractsQueue->push($job);
                $queued[] = $entity->getId();
            }
        }
    }
}
