<?php

declare(strict_types=1);

namespace App\Doctrine\DBAL\Types;

use Acelaya\Doctrine\Type\PhpEnumType;
use App\Enum\CustomerAccountStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class CustomerAccountStatusEnumType extends PhpEnumType
{
    /**
     * Type name.
     */
    const NAME = 'customer_account_status_enum';

    /**
     * {@inheritdoc}
     */
    protected $enumClass = CustomerAccountStatus::class;

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
