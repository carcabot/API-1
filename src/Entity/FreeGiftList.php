<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of free gifts.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"free_gift_list_read"}},
 *     "denormalization_context"={"groups"={"free_gift_list_write"}},
 * })
 */
class FreeGiftList extends ItemList
{
    public function __clone()
    {
        parent::__clone();
    }
}
