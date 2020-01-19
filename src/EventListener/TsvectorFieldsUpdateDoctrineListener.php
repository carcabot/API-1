<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Doctrine\DBAL\Types\TsvectorType;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Event\OnFlushEventArgs;

class TsvectorFieldsUpdateDoctrineListener
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
            $classMetadata = $em->getClassMetadata(ClassUtils::getClass($entity));
            $isInsert = $uow->isScheduledForInsert($entity);
            $isUpdate = $uow->isScheduledForUpdate($entity);
            $hasChanges = false;

            if ($isUpdate) {
                $changeSet = $uow->getEntityChangeSet($entity);
            }

            foreach ($classMetadata->fieldMappings as $field => $mapping) {
                $type = Type::getType($mapping['type']);

                if (!$type instanceof TsvectorType) {
                    continue;
                }

                if (!isset($mapping['options']['tsvector_fields'])) {
                    continue;
                }

                $tsvectorFields = $mapping['options']['tsvector_fields'];
                $expressions = [];
                $parameters = [];
                $computeTsvector = $isInsert;

                foreach ($tsvectorFields as $sourceField => $tsvectorOptions) {
                    if (\is_string($tsvectorOptions)) {
                        $sourceField = $tsvectorOptions;
                        $tsvectorOptions = null;
                    }

                    if ($isUpdate && isset($changeSet[$sourceField])) {
                        $computeTsvector = true;
                    }

                    $documentParam = $sourceField.'_document';

                    $document = $classMetadata->getFieldValue($entity, $sourceField);
                    if (null === $document) {
                        $document = '';
                    } elseif (\is_array($document)) {
                        $document = \implode(' ', $document);
                    }

                    $expression = ':'.$documentParam;
                    $parameters[$documentParam] = $document;

                    if (isset($tsvectorOptions['config'])) {
                        $configParam = $sourceField.'_config';

                        $config = $tsvectorOptions['config'];

                        $expression = ':'.$configParam.', '.$expression;
                        $parameters[$configParam] = $config;
                    }
                    $expression = 'to_tsvector('.$expression.')';

                    if (isset($tsvectorOptions['weight'])) {
                        $weightParam = $sourceField.'_weight';

                        $weight = $tsvectorOptions['weight'];

                        $expression = 'setweight('.$expression.', '.':'.$weightParam.')';
                        $parameters[$weightParam] = $weight;
                    }

                    $expressions[] = $expression;
                }

                if (!$computeTsvector || 0 === \count($expressions)) {
                    continue;
                }

                $concatenatedExpression = '('.\implode(' || ', $expressions).')';

                if ($type->canRequireSQLConversion()) {
                    $concatenatedExpression = $type->convertToPHPValueSQL($concatenatedExpression, $platform);
                }

                $tsvectorValue = $conn->fetchColumn('SELECT '.$concatenatedExpression, $parameters, 0);

                $classMetadata->setFieldValue($entity, $field, $type->convertToPHPValue($tsvectorValue, $platform));
                $hasChanges = true;
            }

            if ($hasChanges) {
                $uow->recomputeSingleEntityChangeSet($classMetadata, $entity);
            }
        }
    }
}
