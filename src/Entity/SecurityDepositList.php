<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of security deposits.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"security_deposit_list_read"}},
 *     "denormalization_context"={"groups"={"security_deposit_list_write"}},
 * })
 */
class SecurityDepositList extends ItemList
{
    public function __clone()
    {
        parent::__clone();
    }
}
