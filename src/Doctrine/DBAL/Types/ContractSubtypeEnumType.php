<?php

declare(strict_types=1);

namespace App\Doctrine\DBAL\Types;

use App\Enum\DwellingType;
use App\Enum\Industry;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class ContractSubtypeEnumType extends Type
{
    /**
     * Type name.
     */
    const NAME = 'contract_subtype_enum';

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

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        // We need not to do any conversion as the type it is actually a normal `string` type.
        // @todo we need to enhance this.
        //
        // if (null === $value) {
        //     return null;
        // }

        // if (!$value instanceof DwellingType && !$value instanceof Industry) {
        //     throw new ConversionException(\sprintf('Expected %s or %s, got %s', DwellingType::class, Industry::class, \gettype($value)));
        // }
        //
        // return $value->getValue();
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        // We need not to do any conversion as the type it is actually a normal `string` type.
        // @todo we need to enhance this.
        // if (null === $value) {
        //     return null;
        // }
        //
        // try {
        //     return new DwellingType($value);
        // } catch (\UnexpectedValueException $e) {
        //     try {
        //         return new Industry($value);
        //     } catch (\UnexpectedValueException $e) {
        //         throw ConversionException::conversionFailed($value, self::NAME);
        //     }
        // }
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
