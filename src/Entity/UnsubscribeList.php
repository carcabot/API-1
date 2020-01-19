<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of Unsubscribers.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"unsubscribe_list_read"}},
 *     "denormalization_context"={"groups"={"unsubscribe_list_write"}},
 * })
 */
class UnsubscribeList extends ItemList
{
}
