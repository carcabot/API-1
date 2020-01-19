<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of items of any sort—for example, Top 10 Movies About Weathermen, or Top 100 Party Songs. Not to be confused with HTML lists, which are often used only for formatting.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *     "item_list"="ItemList",
 *     "addon_service_list"="AddonServiceList",
 *     "campaign_expectation_list"="CampaignExpectationList",
 *     "direct_mail_campaign_source_list"="DirectMailCampaignSourceList",
 *     "email_campaign_source_list"="EmailCampaignSourceList",
 *     "free_gift_list"="FreeGiftList",
 *     "offer_catalog"="OfferCatalog",
 *     "security_deposit_list"="SecurityDepositList",
 *     "sms_campaign_source_list"="SmsCampaignSourceList",
 *     "source_list"="SourceList",
 *     "tariff_rate_list"="TariffRateList",
 *     "unsubscribe_list"="UnsubscribeList",
 * })
 * @ApiResource(iri="http://schema.org/ItemList", attributes={
 *     "normalization_context"={"groups"={"item_list_read"}},
 *     "denormalization_context"={"groups"={"item_list_write"}},
 * })
 */
class ItemList
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    private $description;

    /**
     * @var Collection<ListItem> For itemListElement values, you can use simple strings (e.g. "Peter", "Paul", "Mary"), existing entities, or use ListItem.
     *                           Text values are best if the elements in the list are plain strings. Existing entities are best for a simple, unordered list of existing things in your data. ListItem is used with ordered lists when you want to provide additional context about the element in that list or when the same item might be in different places in different lists.
     *                           Note: The order of elements in your mark-up is not sufficient for indicating the order or elements. Use ListItem with a 'position' property in such cases.
     *
     * @ORM\ManyToMany(targetEntity="ListItem", cascade={"persist"})
     * @ORM\JoinTable(name="item_lists_list_items",
     *      joinColumns={@ORM\JoinColumn(name="item_list_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="list_item_id", referencedColumnName="id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty(iri="http://schema.org/itemListElement")
     * @ApiSubresource()
     */
    private $itemListElement;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    public function __construct()
    {
        $this->itemListElement = new ArrayCollection();
    }

    public function __clone()
    {
        if (null !== $this->id) {
            $itemListElements = new ArrayCollection();
            foreach ($this->itemListElement as $itemListElement) {
                $itemListElements[] = clone $itemListElement;
            }
            $this->itemListElement = $itemListElements;
        }
    }

    /**
     * Get the value of id.
     *
     * @return int|null
     */
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
     * Adds itemListElement.
     *
     * @param ListItem $itemListElement
     *
     * @return $this
     */
    public function addItemListElement(ListItem $itemListElement)
    {
        $this->itemListElement[] = $itemListElement;

        return $this;
    }

    /**
     * Removes itemListElement.
     *
     * @param ListItem $itemListElement
     *
     * @return $this
     */
    public function removeItemListElement(ListItem $itemListElement)
    {
        $this->itemListElement->removeElement($itemListElement);

        return $this;
    }

    /**
     * Get itemListElement.
     *
     * @return ListItem[]
     */
    public function getItemListElement(): array
    {
        return $this->itemListElement->getValues();
    }

    /**
     * Clears itemListElement.
     *
     * @return $this
     */
    public function clearItemListElement()
    {
        $this->itemListElement = new ArrayCollection();

        return $this;
    }

    /**
     * Get the value of name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the value of name.
     *
     * @param ?string $name
     *
     * @return self
     */
    public function setName(?string $name)
    {
        $this->name = $name;

        return $this;
    }
}
