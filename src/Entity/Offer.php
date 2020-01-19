<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\OfferType;
use Doctrine\ORM\Mapping as ORM;

/**
 * An offer to transfer some rights to an item or to provide a service — for example, an offer to sell tickets to an event, to rent the DVD of a movie, to stream a TV show over the internet, to repair a motorcycle, or to loan a book.
 *
 * For GTIN-related fields, see Check Digit calculator and validation guide from GS1.
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Offer", attributes={
 *     "normalization_context"={"groups"={"offer_read"}},
 *     "denormalization_context"={"groups"={"offer_write"}},
 *     "filters"={
 *         "offer.exists",
 *         "offer.order",
 *         "offer.search",
 *     },
 * })
 */
class Offer
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var OfferCategory The offer category.
     *
     * @ORM\ManyToOne(targetEntity="OfferCategory", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $category;

    /**
     * @var string The description of the item.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var DigitalDocument|null An image of the item.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $image;

    /**
     * @var Offer|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="Offer")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var string The name of the item.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var string|null The identifier of the offer.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=true)
     * @ApiProperty()
     */
    protected $offerNumber;

    /**
     * @var Merchant|null An entity which offers (sells / leases / lends / loans) the services / goods. A seller may also be a provider. Supersedes merchant, vendor.
     *
     * @ORM\ManyToOne(targetEntity="Merchant")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/seller")
     */
    protected $seller;

    /**
     * @var string The Stock Keeping Unit (SKU), i.e. a merchant-specific identifier for a product or service, or the product to which the offer refers.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/sku")
     */
    protected $sku;

    /**
     * @var OfferType The offer type.
     *
     * @ORM\Column(type="offer_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var \DateTime|null The valid from date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var \DateTime|null The valid through date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
            $this->isBasedOn = null;
            $this->offerNumber = null;
            $this->image = null;
            $this->seller = null;

            if (null !== $this->validFrom) {
                $this->validFrom = clone $this->validFrom;
            }

            if (null !== $this->validThrough) {
                $this->validThrough = clone $this->validThrough;
            }

            $newCategory = clone $this->category;
            $newCategory->setIsBasedOn($this->category);

            $this->category = $newCategory;
        }
    }

    /**
     * Gets id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets category.
     *
     * @param OfferCategory $category
     *
     * @return $this
     */
    public function setCategory(OfferCategory $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Gets category.
     *
     * @return OfferCategory
     */
    public function getCategory(): OfferCategory
    {
        return $this->category;
    }

    /**
     * Sets description.
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Sets image.
     *
     * @param DigitalDocument|null $image
     *
     * @return $this
     */
    public function setImage(?DigitalDocument $image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Gets image.
     *
     * @return DigitalDocument|null
     */
    public function getImage(): ?DigitalDocument
    {
        return $this->image;
    }

    /**
     * Gets isBased on.
     *
     * @return Offer|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * Sets isBasedOn.
     *
     * @param Offer|null $isBasedOn
     *
     * @return $this
     */
    public function setIsBasedOn(?self $isBasedOn)
    {
        $this->isBasedOn = $isBasedOn;

        return $this;
    }

    /**
     * Sets name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets offer number.
     *
     * @return string|null
     */
    public function getOfferNumber(): ?string
    {
        return $this->offerNumber;
    }

    /**
     * Sets offer number.
     *
     * @param string|null $offerNumber
     *
     * @return $this
     */
    public function setOfferNumber(?string $offerNumber)
    {
        $this->offerNumber = $offerNumber;

        return $this;
    }

    /**
     * Sets seller.
     *
     * @param Merchant|null $seller
     *
     * @return $this
     */
    public function setSeller(?Merchant $seller)
    {
        $this->seller = $seller;

        return $this;
    }

    /**
     * Gets seller.
     *
     * @return Merchant|null
     */
    public function getSeller(): ?Merchant
    {
        return $this->seller;
    }

    /**
     * Sets sku.
     *
     * @param string $sku
     *
     * @return $this
     */
    public function setSku(string $sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Gets sku.
     *
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * Gets type.
     *
     * @return OfferType
     */
    public function getType(): OfferType
    {
        return $this->type;
    }

    /**
     * Sets type.
     *
     * @param OfferType $type
     *
     * @return $this
     */
    public function setType(OfferType $type)
    {
        $this->type = $type;

        return $this;
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
