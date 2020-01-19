<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * An list item, e.g. a step in a checklist or how-to description.
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/ListItem", attributes={
 *      "normalization_context"={"groups"={"list_item_read"}},
 *      "denormalization_context"={"groups"={"list_item_write"}},
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *      "addon_service_list_item"="AddonServiceListItem",
 *      "application_request_list_item"="ApplicationRequestListItem",
 *      "campaign_expectation_list_item"="CampaignExpectationListItem",
 *      "campaign_source_list_item"="CampaignSourceListItem",
 *      "customer_account_list_item"="CustomerAccountListItem",
 *      "direct_mail_campaign_source_list_item"="DirectMailCampaignSourceListItem",
 *      "email_campaign_source_list_item"="EmailCampaignSourceListItem",
 *      "free_gift_list_item"="FreeGiftListItem",
 *      "lead_list_item"="LeadListItem",
 *      "list_item"="ListItem",
 *      "offer_list_item"="OfferListItem",
 *      "security_deposit_list_item"="SecurityDepositListItem",
 *      "sms_campaign_source_list_item"="SmsCampaignSourceListItem",
 *      "tariff_rate_list_item"="TariffRateListItem",
 *      "unsubscribe_list_item"="UnsubscribeListItem",
 *     "message_recipient_list_item"="MessageRecipientListItem",
 * })
 */
class ListItem
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var int|null The position of an item in a series or sequence of items.
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ApiProperty(iri="http://schema.org/position")
     */
    protected $position;

    /**
     * @var string|null The value of the quantitative value or property value node.
     *                  For QuantitativeValue and MonetaryAmount, the recommended type for values is 'Number'.
     *                  For PropertyValue, it can be 'Text;', 'Number', 'Boolean', or 'StructuredValue'.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(iri="http://schema.org/value")
     */
    protected $value;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the value of description.
     *
     * @param ?string $description
     *
     * @return self
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the value of name.
     *
     * @param string|null $name
     *
     * @return self
     */
    public function setName(?string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of position.
     *
     * @return int
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Set the value of position.
     *
     * @param int $position
     *
     * @return self
     */
    public function setPosition(?int $position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get the value of value.
     *
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set the value of value.
     *
     * @param string $value
     *
     * @return self
     */
    public function setValue(?string $value)
    {
        $this->value = $value;

        return $this;
    }
}
