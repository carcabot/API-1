<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * An add-on service list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"addon_service_list_item_read"}},
 *     "denormalization_context"={"groups"={"addon_service_list_item_write"}},
 * })
 */
class AddonServiceListItem extends ListItem
{
    /**
     * @var AddonService An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="AddonService", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/item")
     */
    protected $item;

    public function __clone()
    {
        parent::__clone();

        $newItem = clone $this->item;
        $newItem->setIsBasedOn($this->item);

        $this->item = $newItem;
    }

    /**
     * Sets item.
     *
     * @param AddonService $item
     *
     * @return $this
     */
    public function setItem(AddonService $item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Gets item.
     *
     * @return AddonService
     */
    public function getItem(): AddonService
    {
        return $this->item;
    }
}
