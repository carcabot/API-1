<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A customer account list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"customer_account_list_item_read"}},
 *     "denormalization_context"={"groups"={"customer_account_list_item_write"}},
 * })
 */
class CustomerAccountListItem extends ListItem
{
    /**
     * @var CustomerAccount An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/item")
     */
    protected $item;

    /**
     * Get the value of item.
     */
    public function getItem(): CustomerAccount
    {
        return $this->item;
    }

    /**
     * Set the value of item.
     *
     * @param CustomerAccount $item
     *
     * @return self
     */
    public function setItem(CustomerAccount $item)
    {
        $this->item = $item;

        return $this;
    }
}
