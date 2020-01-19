<?php
/*
 * This file is part of the U-Centric project.
 *
 * (c) U-Centric Development Team <dev@ucentric.sisgroup.sg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Doctrine\DBAL\Types;

use Acelaya\Doctrine\Type\PhpEnumType;
use App\Enum\QuotationStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class QuotationStatusEnumType extends PhpEnumType
{
    /**
     * Type name.
     */
    const NAME = 'quotation_status_enum';

    /**
     * {@inheritdoc}
     */
    protected $enumClass = QuotationStatus::class;

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
