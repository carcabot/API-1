<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of tariff rates.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"tariff_rate_list_read"}},
 *     "denormalization_context"={"groups"={"tariff_rate_list_write"}},
 * })
 */
class TariffRateList extends ItemList
{
}
