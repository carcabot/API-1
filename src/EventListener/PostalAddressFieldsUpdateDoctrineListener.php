<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\PostalAddress;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;

class PostalAddressFieldsUpdateDoctrineListener
{
    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $conn = $em->getConnection();
        $platform = $conn->getDatabasePlatform();

        $entities = \array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        foreach ($entities as $entity) {
            if (PostalAddress::class !== ClassUtils::getClass($entity)) {
                continue;
            }
            $classMetadata = $em->getClassMetadata(ClassUtils::getClass($entity));
            $hasChanges = false;

            if ($entity->getText() !== $entity->__toString()) {
                $entity->setText($entity->__toString());
                $hasChanges = true;
            }

            if ($hasChanges) {
                $uow->recomputeSingleEntityChangeSet($classMetadata, $entity);
            }
        }
    }
}
