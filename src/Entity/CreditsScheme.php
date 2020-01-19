<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\CreditsType;
use Doctrine\ORM\Mapping as ORM;

/**
 * The credits scheme.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *     "credits_scheme"="CreditsScheme",
 *     "referral_credits_scheme"="ReferralCreditsScheme",
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"credits_scheme_read"}},
 *     "denormalization_context"={"groups"={"credits_scheme_write"}},
 *     "filters"={
 *         "credits_scheme.exists",
 *         "credits_scheme.order",
 *         "credits_scheme.search",
 *     },
 * })
 */
class CreditsScheme
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
     * @var QuantitativeValue Amount of credits to be earned.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $amount;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var CreditsScheme|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="CreditsScheme")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var MonetaryAmount The amount of money.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $monetaryExchangeValue;

    /**
     * @var string|null The external ID used for reference.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $schemeId;

    /**
     * @var CreditsType The credits type.
     *
     * @ORM\Column(type="credits_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var \DateTime|null The date when the item becomes valid.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var QuantitativeValue The validity period.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $validPeriod;

    /**
     * @var \DateTime|null The date after when the item is not valid. For example the end of an offer, salary period, or a period of opening hours.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    public function __construct()
    {
        $this->amount = new QuantitativeValue();
        $this->monetaryExchangeValue = new MonetaryAmount();
        $this->validPeriod = new QuantitativeValue();
    }

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
            $this->isBasedOn = null;

            if (null !== $this->validFrom) {
                $this->validFrom = clone $this->validFrom;
            }

            if (null !== $this->validThrough) {
                $this->validThrough = clone $this->validThrough;
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
     * Sets amount.
     *
     * @param QuantitativeValue $amount
     *
     * @return $this
     */
    public function setAmount(QuantitativeValue $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Gets amount.
     *
     * @return QuantitativeValue
     */
    public function getAmount(): QuantitativeValue
    {
        return $this->amount;
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
     * Gets isBasedOn.
     *
     * @return CreditsScheme|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * Sets isBasedOn.
     *
     * @param self|null $isBasedOn
     *
     * @return $this
     */
    public function setIsBasedOn(?self $isBasedOn)
    {
        $this->isBasedOn = $isBasedOn;

        return $this;
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
     * Sets schemeId.
     *
     * @param string|null $schemeId
     *
     * @return $this
     */
    public function setSchemeId(?string $schemeId)
    {
        $this->schemeId = $schemeId;

        return $this;
    }

    /**
     * Gets schemeId.
     *
     * @return string|null
     */
    public function getSchemeId(): ?string
    {
        return $this->schemeId;
    }

    /**
     * Sets type.
     *
     * @param CreditsType $type
     *
     * @return $this
     */
    public function setType(CreditsType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return CreditsType
     */
    public function getType(): CreditsType
    {
        return $this->type;
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
     * Sets validPeriod.
     *
     * @param QuantitativeValue $validPeriod
     *
     * @return $this
     */
    public function setValidPeriod(QuantitativeValue $validPeriod)
    {
        $this->validPeriod = $validPeriod;

        return $this;
    }

    /**
     * Gets validPeriod.
     *
     * @return QuantitativeValue
     */
    public function getValidPeriod(): QuantitativeValue
    {
        return $this->validPeriod;
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
