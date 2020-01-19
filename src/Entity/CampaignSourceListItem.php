<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\CampaignSourceType;
use Doctrine\ORM\Mapping as ORM;

/**
 * A campaign source list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *      "normalization_context"={"groups"={"campaign_source_list_item_read"}},
 *      "denormalization_context"={"groups"={"campaign_source_list_item_write"}},
 * })
 */
class CampaignSourceListItem extends ListItem
{
    /**
     * @var CampaignSourceType Source type.
     *
     * @ORM\Column(type="campaign_source_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $source;

    /**
     * @var CustomerAccount|null
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $customer;

    /**
     * @var Lead|null
     *
     * @ORM\ManyToOne(targetEntity="Lead")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $lead;

    /**
     * Gets campaign source type.
     *
     * @return CampaignSourceType
     */
    public function getSource(): CampaignSourceType
    {
        return $this->source;
    }

    /**
     * Sets campaign source type.
     *
     * @param CampaignSourceType $source
     */
    public function setSource(CampaignSourceType $source): void
    {
        $this->source = $source;
    }

    /**
     * Gets cutomser account.
     *
     * @return CustomerAccount|null
     */
    public function getCustomer(): ?CustomerAccount
    {
        return $this->customer;
    }

    /**
     * Sets customer account.
     *
     * @param CustomerAccount|null $customer
     */
    public function setCustomer(?CustomerAccount $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * Gets lead.
     *
     * @return Lead|null
     */
    public function getLead(): ?Lead
    {
        return $this->lead;
    }

    /**
     * Sets lead.
     *
     * @param Lead|null $lead
     */
    public function setLead(?Lead $lead): void
    {
        $this->lead = $lead;
    }
}
