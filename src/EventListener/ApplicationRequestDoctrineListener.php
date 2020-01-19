<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Disque\JobType;
use App\Entity\ApplicationRequest;
use Disque\Queue\Job as DisqueJob;
use Disque\Queue\Queue as DisqueQueue;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;

class ApplicationRequestDoctrineListener
{
    /**
     * @var DisqueQueue
     */
    protected $applicationRequestQueue;

    /**
     * @param DisqueQueue $applicationRequestQueue
     */
    public function __construct(DisqueQueue $applicationRequestQueue)
    {
        $this->applicationRequestQueue = $applicationRequestQueue;
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $queued = [];

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (ApplicationRequest::class !== ClassUtils::getClass($entity)) {
                continue;
            }

            if (!\in_array($entity->getId(), $queued, true)) {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $entity->getId(),
                        'mode' => 'insert',
                    ],
                    'type' => JobType::APPLICATION_REQUEST_UPDATE_CACHE_TABLE,
                ]);

                $this->applicationRequestQueue->push($job);
                $queued[] = $entity->getId();
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (ApplicationRequest::class !== ClassUtils::getClass($entity)) {
                continue;
            }

            if (!\in_array($entity->getId(), $queued, true)) {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $entity->getId(),
                        'mode' => 'update',
                    ],
                    'type' => JobType::APPLICATION_REQUEST_UPDATE_CACHE_TABLE,
                ]);

                $this->applicationRequestQueue->push($job);
                $queued[] = $entity->getId();
            }
        }

        foreach ($uow->getScheduledCollectionUpdates() as $entity) {
            if (ApplicationRequest::class !== ClassUtils::getClass($entity)) {
                continue;
            }

            if (!\in_array($entity->getId(), $queued, true)) {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $entity->getId(),
                        'mode' => 'update',
                    ],
                    'type' => JobType::APPLICATION_REQUEST_UPDATE_CACHE_TABLE,
                ]);

                $this->applicationRequestQueue->push($job);
                $queued[] = $entity->getId();
            }
        }

        foreach ($uow->getScheduledCollectionDeletions() as $entity) {
            if (ApplicationRequest::class !== ClassUtils::getClass($entity)) {
                continue;
            }

            if (!\in_array($entity->getId(), $queued, true)) {
                $job = new DisqueJob([
                    'data' => [
                        'id' => $entity->getId(),
                        'mode' => 'delete',
                    ],
                    'type' => JobType::APPLICATION_REQUEST_UPDATE_CACHE_TABLE,
                ]);

                $this->applicationRequestQueue->push($job);
                $queued[] = $entity->getId();
            }
        }
    }
}
