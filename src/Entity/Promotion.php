<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\PromotionStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The promotion.
 *
 * @ORM\Entity(repositoryClass="App\Repository\PromotionRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"promotion_number"}),
 * })
 *
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"promotion_read"}},
 *     "denormalization_context"={"groups"={"promotion_write"}},
 *     "filters"={
 *          "promotion.date",
 *          "promotion.exists",
 *          "promotion.json_search",
 *          "promotion.search"
 *     },
 * })
 */
class Promotion
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
     * @var QuantitativeValue The value of promotion, in percentage.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $amount;

    /**
     * @var PromotionCategory The promotion category.
     *
     * @ORM\ManyToOne(targetEntity="PromotionCategory", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $category;

    /**
     * @var string[] Contracts types the promotion will be applicable for.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $contractTypes;

    /**
     * @var string|null The currency in which the monetary amount is expressed.
     *
     * Use standard formats: ISO 4217 currency format e.g. "USD"; Ticker symbol for cryptocurrencies e.g. "BTC"; well known names for Local Exchange Tradings Systems (LETS) and other currency types e.g. "Ithaca HOUR".
     *
     * @ORM\Column(type="string", length=3, nullable=true)
     * @ApiProperty(iri="http://schema.org/currency")
     */
    protected $currency;

    /**
     * @var string[] The customer account types promotion valid for.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $customerTypes;

    /**
     * @var QuantitativeValue The current approximate inventory level for the item or items.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty(iri="http://schema.org/inventoryLevel")
     */
    protected $inventoryLevel;

    /**
     * @var Promotion|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="Promotion")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var WebPage|null The promotion web page.
     *
     * @ORM\OneToOne(targetEntity="WebPage")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $page;

    /**
     * @ORM\Column(type="string", length=128, nullable=false)
     * @ApiProperty()
     */
    protected $promotionNumber;

    /**
     * @var QuantitativeValue The number of recurring duration (Months) the promotion can be applied.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $recurringDuration;

    /**
     * @var PromotionStatus The status of the promotion.
     *
     * @ORM\Column(type="promotion_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var Collection<TariffRate> Tariff rates the promotion is applied to.
     *
     * @ORM\ManyToMany(targetEntity="TariffRate", mappedBy="promotions")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $tariffRates;

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
        $this->contractTypes = [];
        $this->customerTypes = [];
        $this->inventoryLevel = new QuantitativeValue();
        $this->amount = new QuantitativeValue();
        $this->tariffRates = new ArrayCollection();
    }

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
            $this->page = null;

            if (null !== $this->category) {
                $this->category = clone $this->category;
            }

            if (null !== $this->amount) {
                $this->amount = clone $this->amount;
            }

            if (null !== $this->validFrom) {
                $this->validFrom = clone $this->validFrom;
            }

            if (null !== $this->validThrough) {
                $this->validThrough = clone $this->validThrough;
            }
        }
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return QuantitativeValue
     */
    public function getAmount(): QuantitativeValue
    {
        return $this->amount;
    }

    /**
     * @param QuantitativeValue $amount
     */
    public function setAmount(QuantitativeValue $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return PromotionCategory
     */
    public function getCategory(): PromotionCategory
    {
        return $this->category;
    }

    /**
     * @param PromotionCategory $category
     */
    public function setCategory(PromotionCategory $category): void
    {
        $this->category = $category;
    }

    /**
     * Adds contractType.
     *
     * @param string $contractType
     *
     * @return $this
     */
    public function addContractType(string $contractType)
    {
        $this->contractTypes[] = $contractType;

        return $this;
    }

    /**
     * Removes contractType.
     *
     * @param string $contractType
     *
     * @return $this
     */
    public function removeContractType(string $contractType)
    {
        if (false !== ($key = \array_search($contractType, $this->contractTypes, true))) {
            \array_splice($this->contractTypes, $key, 1);
        }

        return $this;
    }

    /**
     * Clears all contractTypes.
     *
     * @return $this
     */
    public function clearContractTypes()
    {
        $this->contractTypes = [];

        return $this;
    }

    /**
     * Gets contractTypes.
     *
     * @return string[]
     */
    public function getContractTypes(): array
    {
        return $this->contractTypes;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     */
    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string[]
     */
    public function getCustomerTypes(): array
    {
        return $this->customerTypes;
    }

    /**
     * @param string[] $customerTypes
     */
    public function setCustomerTypes(array $customerTypes): void
    {
        $this->customerTypes = $customerTypes;
    }

    /**
     * @return QuantitativeValue
     */
    public function getInventoryLevel(): QuantitativeValue
    {
        return $this->inventoryLevel;
    }

    /**
     * @param QuantitativeValue $inventoryLevel
     */
    public function setInventoryLevel(QuantitativeValue $inventoryLevel): void
    {
        $this->inventoryLevel = $inventoryLevel;
    }

    /**
     * @return Promotion|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * @param Promotion|null $isBasedOn
     */
    public function setIsBasedOn(?self $isBasedOn): void
    {
        $this->isBasedOn = $isBasedOn;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return WebPage|null
     */
    public function getPage(): ?WebPage
    {
        return $this->page;
    }

    /**
     * @param WebPage|null $page
     */
    public function setPage(?WebPage $page): void
    {
        $this->page = $page;
    }

    /**
     * @return string
     */
    public function getPromotionNumber(): string
    {
        return $this->promotionNumber;
    }

    /**
     * @param string $promotionNumber
     */
    public function setPromotionNumber(string $promotionNumber): void
    {
        $this->promotionNumber = $promotionNumber;
    }

    /**
     * @return QuantitativeValue
     */
    public function getRecurringDuration(): QuantitativeValue
    {
        return $this->recurringDuration;
    }

    /**
     * @param QuantitativeValue $recurringDuration
     */
    public function setRecurringDuration(QuantitativeValue $recurringDuration): void
    {
        $this->recurringDuration = $recurringDuration;
    }

    /**
     * @return PromotionStatus
     */
    public function getStatus(): PromotionStatus
    {
        return $this->status;
    }

    /**
     * @param PromotionStatus $status
     */
    public function setStatus(PromotionStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * Adds tariffRate.
     *
     * @param TariffRate $tariffRate
     *
     * @return $this
     */
    public function addTariffRate(TariffRate $tariffRate)
    {
        $tariffRate->addPromotion($this);
        $this->tariffRates[] = $tariffRate;

        return $this;
    }

    /**
     * Removes tariffRate.
     *
     * @param TariffRate $tariffRate
     *
     * @return $this
     */
    public function removeTariffRate(TariffRate $tariffRate)
    {
        $tariffRate->removePromotion($this);
        $this->tariffRates->removeElement($tariffRate);

        return $this;
    }

    /**
     * Gets tariffRates.
     *
     * @return TariffRate[]
     */
    public function getTariffRates(): array
    {
        return $this->tariffRates->getValues();
    }

    /**
     * @return \DateTime|null
     */
    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
     * @param \DateTime|null $validFrom
     */
    public function setValidFrom(?\DateTime $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidThrough(): ?\DateTime
    {
        return $this->validThrough;
    }

    /**
     * @param \DateTime|null $validThrough
     */
    public function setValidThrough(?\DateTime $validThrough): void
    {
        $this->validThrough = $validThrough;
    }
}
