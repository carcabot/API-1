<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A lead list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"lead_list_item_read"}},
 *     "denormalization_context"={"groups"={"lead_list_item_write"}},
 * })
 */
class LeadListItem extends ListItem
{
    /**
     * @var Lead An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="Lead")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/item")
     */
    protected $item;

    /**
     * Get the value of item.
     */
    public function getItem(): Lead
    {
        return $this->item;
    }

    /**
     * Set the value of item.
     *
     * @param Lead $item
     *
     * @return self
     */
    public function setItem(Lead $item)
    {
        $this->item = $item;

        return $this;
    }
}
