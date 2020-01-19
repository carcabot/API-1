<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A tariff rate list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"tariff_rate_list_item_read"}},
 *     "denormalization_context"={"groups"={"tariff_rate_list_item_write"}},
 * })
 */
class TariffRateListItem extends ListItem
{
    /**
     * @var TariffRate An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="TariffRate")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/item")
     */
    protected $item;

    /**
     * Sets item.
     *
     * @param TariffRate $item
     *
     * @return $this
     */
    public function setItem(TariffRate $item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Gets item.
     *
     * @return TariffRate
     */
    public function getItem(): TariffRate
    {
        return $this->item;
    }
}
