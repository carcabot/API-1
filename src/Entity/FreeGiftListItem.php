<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A free gift list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"free_gift_list_item_read"}},
 *     "denormalization_context"={"groups"={"free_gift_list_item_write"}},
 * })
 */
class FreeGiftListItem extends ListItem
{
    /**
     * @var FreeGift An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="FreeGift", cascade={"persist"})
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
     * @param FreeGift $item
     *
     * @return $this
     */
    public function setItem(FreeGift $item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Gets item.
     *
     * @return FreeGift
     */
    public function getItem(): FreeGift
    {
        return $this->item;
    }
}
