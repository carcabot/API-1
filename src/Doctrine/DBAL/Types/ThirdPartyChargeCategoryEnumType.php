<?php

declare(strict_types=1);

namespace App\Doctrine\DBAL\Types;

use App\Enum\ThirdPartyChargeCategory;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class ThirdPartyChargeCategoryEnumType extends Type
{
    /**
     * Type name.
     */
    const NAME = 'third_party_charge_enum';

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
        if (null === $value) {
            return null;
        }

        if (!$value instanceof ThirdPartyChargeCategory) {
            throw new ConversionException(\sprintf('Expected %s, got %s', ThirdPartyChargeCategory::class, \gettype($value)));
        }

        return $value->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        try {
            return new ThirdPartyChargeCategory($value);
        } catch (\UnexpectedValueException $e) {
            throw ConversionException::conversionFailed($value, self::NAME);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
