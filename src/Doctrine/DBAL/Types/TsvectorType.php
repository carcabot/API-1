<?php

declare(strict_types=1);

namespace App\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TsvectorType extends Type
{
    /**
     * Type name.
     */
    const NAME = 'tsvector';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'tsvector';
    }

    /**
     * {@inheritdoc}
     */
    public function canRequireSQLConversion()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return \sprintf('%s::tsvector', $sqlExpr);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return \sprintf('%s::text', $sqlExpr);
    }

    /**
     * {@inheritdoc}
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform)
    {
        return [
            'tsvector',
        ];
    }
}
