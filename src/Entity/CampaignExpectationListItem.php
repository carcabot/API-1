<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A campaign expectation list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"campaign_expectation_list_item_read"}},
 *     "denormalization_context"={"groups"={"campaign_expectation_list_item_write"}},
 * })
 */
class CampaignExpectationListItem extends ListItem
{
    /**
     * @var CampaignExpectation An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="CampaignExpectation")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/item")
     */
    protected $item;

    /**
     * Gets item.
     *
     * @return CampaignExpectation
     */
    public function getItem(): CampaignExpectation
    {
        return $this->item;
    }

    /**
     * Sets item.
     *
     * @param CampaignExpectation $item
     */
    public function setItem(CampaignExpectation $item): void
    {
        $this->item = $item;
    }
}
