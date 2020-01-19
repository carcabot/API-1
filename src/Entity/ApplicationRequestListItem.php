<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @var ApplicationRequest An application request list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"application_request_list_item_read"}},
 *     "denormalization_context"={"groups"={"application_request_list_item_write"}},
 * })
 */
class ApplicationRequestListItem extends ListItem
{
    /**
     * @var ApplicationRequest An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="ApplicationRequest")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/item")
     */
    protected $item;

    /**
     * Get the value of item.
     */
    public function getItem(): ApplicationRequest
    {
        return $this->item;
    }

    /**
     * Set the value of item.
     *
     * @param ApplicationRequest $item
     *
     * @return self
     */
    public function setItem(ApplicationRequest $item)
    {
        $this->item = $item;

        return $this;
    }
}
