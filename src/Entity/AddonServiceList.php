<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of add-on services.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"addon_service_list_read"}},
 *     "denormalization_context"={"groups"={"addon_service_list_write"}},
 * })
 */
class AddonServiceList extends ItemList
{
    public function __clone()
    {
        parent::__clone();
    }
}
