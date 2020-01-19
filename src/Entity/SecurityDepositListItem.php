<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A security deposit list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"security_deposit_list_item_read"}},
 *     "denormalization_context"={"groups"={"security_deposit_list_item_write"}},
 * })
 */
class SecurityDepositListItem extends ListItem
{
    /**
     * @var SecurityDeposit An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="SecurityDeposit", cascade={"persist"})
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
     * @param SecurityDeposit $item
     *
     * @return $this
     */
    public function setItem(SecurityDeposit $item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Gets item.
     *
     * @return SecurityDeposit
     */
    public function getItem(): SecurityDeposit
    {
        return $this->item;
    }
}
