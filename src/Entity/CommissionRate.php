<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\CommissionCategory;
use App\Enum\CommissionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A commission rate.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"commission_rate_read"}},
 *     "denormalization_context"={"groups"={"commission_rate_write"}},
 *     "filters"={
 *         "commission_rate.exists",
 *         "commission_rate.order",
 *     },
 * })
 */
class CommissionRate
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
     * @var CommissionCategory The commission rate category.
     *
     * @ORM\Column(type="commission_category_enum", nullable=false)
     * @ApiProperty()
     */
    protected $category;

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
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var \DateTime|null The end date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/endDate")
     */
    protected $endDate;

    /**
     * @var CommissionRate|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="CommissionRate")
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
     * @var Collection<Partner> The partners that the commission rate applies to.
     *
     * @ORM\ManyToMany(targetEntity="Partner", mappedBy="commissionRates")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $partners;

    /**
     * @var \DateTime|null The start date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/startDate")
     */
    protected $startDate;

    /**
     * @var CommissionType The commission rate type.
     *
     * @ORM\Column(type="commission_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var string|null The commission rate.
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     * @ApiProperty()
     */
    protected $value;

    public function __construct()
    {
        $this->partners = new ArrayCollection();
    }

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;

            if (null !== $this->endDate) {
                $this->endDate = clone $this->endDate;
            }

            $this->partners = new ArrayCollection();

            if (null !== $this->startDate) {
                $this->startDate = clone $this->startDate;
            }
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
     * @param CommissionCategory $category
     *
     * @return $this
     */
    public function setCategory(CommissionCategory $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Gets category.
     *
     * @return CommissionCategory
     */
    public function getCategory(): CommissionCategory
    {
        return $this->category;
    }

    /**
     * Sets currency.
     *
     * @param string|null $currency
     *
     * @return $this
     */
    public function setCurrency(?string $currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Gets currency.
     *
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * Sets description.
     *
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets endDate.
     *
     * @param \DateTime|null $endDate
     *
     * @return $this
     */
    public function setEndDate(?\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Gets endDate.
     *
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * Sets isBasedOn.
     *
     * @param CommissionRate|null $isBasedOn
     *
     * @return $this
     */
    public function setIsBasedOn(?self $isBasedOn)
    {
        $this->isBasedOn = $isBasedOn;

        return $this;
    }

    /**
     * Gets isBasedOn.
     *
     * @return CommissionRate|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
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
     * Adds partner.
     *
     * @param Partner $partner
     *
     * @return $this
     */
    public function addPartner(Partner $partner)
    {
        $this->partners[] = $partner;
        $partner->addCommissionRate($this);

        return $this;
    }

    /**
     * Removes partner.
     *
     * @param Partner $partner
     *
     * @return $this
     */
    public function removePartner(Partner $partner)
    {
        $this->partners->removeElement($partner);
        $partner->removeCommissionRate($this);

        return $this;
    }

    /**
     * Gets partners.
     *
     * @return Partner[]
     */
    public function getPartners(): array
    {
        return $this->partners->getValues();
    }

    /**
     * Sets startDate.
     *
     * @param \DateTime|null $startDate
     *
     * @return $this
     */
    public function setStartDate(?\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Gets startDate.
     *
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * Sets type.
     *
     * @param CommissionType $type
     *
     * @return $this
     */
    public function setType(CommissionType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return CommissionType
     */
    public function getType(): CommissionType
    {
        return $this->type;
    }

    /**
     * Sets value.
     *
     * @param string|null $value
     *
     * @return $this
     */
    public function setValue(?string $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets value.
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Returns whether the commission rate is active based on startDate and endDate.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $now = new \DateTime();

        if (null !== $this->startDate && $now >= $this->startDate) {
            if (null === $this->endDate || $now < $this->endDate) {
                return true;
            }
        }

        return false;
    }
}
