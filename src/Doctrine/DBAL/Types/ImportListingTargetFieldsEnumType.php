<?php

declare(strict_types=1);

namespace App\Doctrine\DBAL\Types;

use Acelaya\Doctrine\Type\PhpEnumType;
use App\Enum\ImportListingTargetFields;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class ImportListingTargetFieldsEnumType extends PhpEnumType
{
    /**
     * Type name.
     */
    const NAME = 'import_listing_target_fields_enum';

    /**
     * {@inheritdoc}
     */
    protected $enumClass = ImportListingTargetFields::class;

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
        return $platform->getVarcharTypeDeclarationSQL([
            'length' => 254,
        ]);
    }
}
