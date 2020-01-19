<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * An offer list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"offer_list_item_read"}},
 *     "denormalization_context"={"groups"={"offer_list_item_write"}},
 *     "filters"={
 *         "offer_list_item.date",
 *         "offer_list_item.exists",
 *     },
 * })
 */
class OfferListItem extends ListItem
{
    /**
     * @var QuantitativeValue The current approximate inventory level for the item or items.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty(iri="http://schema.org/inventoryLevel")
     */
    protected $inventoryLevel;

    /**
     * @var OfferListItem|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="OfferListItem")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var Offer An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="Offer", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/item")
     */
    protected $item;

    /**
     * @var MonetaryAmount The monetary exchange value for the offer.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $monetaryExchangeValue;

    /**
     * @var PriceSpecification The offer price of a product, or of a price component when attached to PriceSpecification and its subtypes.
     *
     * @ORM\Embedded(class="PriceSpecification")
     * @ApiProperty(iri="http://schema.org/price")
     */
    protected $priceSpecification;

    /**
     * @var \DateTime|null The date when the item becomes valid.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var \DateTime|null The date after when the item is not valid. For example the end of an offer, salary period, or a period of opening hours.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    public function __construct()
    {
        $this->inventoryLevel = new QuantitativeValue();
        $this->monetaryExchangeValue = new MonetaryAmount();
        $this->priceSpecification = new PriceSpecification();
    }

    public function __clone()
    {
        if (null !== $this->id) {
            parent::__clone();

            $this->isBasedOn = null;

            if (null !== $this->validFrom) {
                $this->validFrom = clone $this->validFrom;
            }

            if (null !== $this->validThrough) {
                $this->validThrough = clone $this->validThrough;
            }

            $newItem = clone $this->item;
            $newItem->setIsBasedOn($this->item);

            $this->item = $newItem;
        }
    }

    /**
     * Sets inventoryLevel.
     *
     * @param QuantitativeValue $inventoryLevel
     *
     * @return $this
     */
    public function setInventoryLevel(QuantitativeValue $inventoryLevel)
    {
        $this->inventoryLevel = $inventoryLevel;

        return $this;
    }

    /**
     * Gets inventoryLevel.
     *
     * @return QuantitativeValue
     */
    public function getInventoryLevel(): QuantitativeValue
    {
        return $this->inventoryLevel;
    }

    /**
     * Gets isBasedOn.
     *
     * @return OfferListItem|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * Sets isBasedOn.
     *
     * @param OfferListItem|null $isBasedOn
     *
     * @return $this
     */
    public function setIsBasedOn(?self $isBasedOn)
    {
        $this->isBasedOn = $isBasedOn;

        return $this;
    }

    /**
     * Sets item.
     *
     * @param Offer $item
     *
     * @return $this
     */
    public function setItem(Offer $item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Gets item.
     *
     * @return Offer
     */
    public function getItem(): Offer
    {
        return $this->item;
    }

    /**
     * Sets monetaryExchangeValue.
     *
     * @param MonetaryAmount $monetaryExchangeValue
     *
     * @return $this
     */
    public function setMonetaryExchangeValue(MonetaryAmount $monetaryExchangeValue)
    {
        $this->monetaryExchangeValue = $monetaryExchangeValue;

        return $this;
    }

    /**
     * Gets monetaryExchangeValue.
     *
     * @return MonetaryAmount
     */
    public function getMonetaryExchangeValue(): MonetaryAmount
    {
        return $this->monetaryExchangeValue;
    }

    /**
     * Sets price.
     *
     * @param PriceSpecification $priceSpecification
     *
     * @return $this
     */
    public function setPriceSpecification(PriceSpecification $priceSpecification)
    {
        $this->priceSpecification = $priceSpecification;

        return $this;
    }

    /**
     * Gets priceSpecification.
     *
     * @return PriceSpecification
     */
    public function getPriceSpecification(): PriceSpecification
    {
        return $this->priceSpecification;
    }

    /**
     * Sets validFrom.
     *
     * @param \DateTime|null $validFrom
     *
     * @return $this
     */
    public function setValidFrom(?\DateTime $validFrom)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * Gets validFrom.
     *
     * @return \DateTime|null
     */
    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
     * Sets validThrough.
     *
     * @param \DateTime|null $validThrough
     *
     * @return $this
     */
    public function setValidThrough(?\DateTime $validThrough)
    {
        $this->validThrough = $validThrough;

        return $this;
    }

    /**
     * Gets validThrough.
     *
     * @return \DateTime|null
     */
    public function getValidThrough(): ?\DateTime
    {
        return $this->validThrough;
    }
}
