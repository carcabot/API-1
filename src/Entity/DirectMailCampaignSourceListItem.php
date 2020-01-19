<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A direct mail source list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"direct_mail_campaign_source_list_item_read"}},
 *     "denormalization_context"={"groups"={"direct_mail_campaign_source_list_item_write"}},
 * })
 */
class DirectMailCampaignSourceListItem extends CampaignSourceListItem
{
    /**
     * @var PostalAddress An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\OneToOne(targetEntity="PostalAddress")
     * @ApiProperty(iri="http://schema.org/item")
     */
    protected $item;

    /**
     * Gets postal address.
     *
     * @return PostalAddress
     */
    public function getItem(): PostalAddress
    {
        return $this->item;
    }

    /**
     * Sets postal address.
     *
     * @param PostalAddress $item
     */
    public function setItem(PostalAddress $item): void
    {
        $this->item = $item;
    }
}
