<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The exchange rate for point credits.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"point_credits_exchange_rate_read"}},
 *     "denormalization_context"={"groups"={"point_credits_exchange_rate_write"}},
 *     "filters"={
 *         "point_credits_exchange_rate.date",
 *         "point_credits_exchange_rate.exists",
 *         "point_credits_exchange_rate.order",
 *     },
 * })
 */
class PointCreditsExchangeRate
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
     * @var MonetaryAmount The base amount.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $baseAmount;

    /**
     * @var bool|null Determines whether the exchange rate is the default.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $defaultRate;

    /**
     * @var PointCreditsExchangeRate|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="PointCreditsExchangeRate")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var string|null A remark.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $remark;

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

    /**
     * @var string The value of the quantitative value or property value node.
     *
     * - For [QuantitativeValue](http://schema.org/QuantitativeValue) and [MonetaryAmount](http://schema.org/MonetaryAmount, the recommended type for values is 'Number'.
     * - For [PropertyValue](http://schema.org/PropertyValue), it can be 'Text;', 'Number', 'Boolean', or 'StructuredValue'.
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=false)
     * @ApiProperty(iri="http://schema.org/value")
     */
    protected $value;

    public function __construct()
    {
        $this->baseAmount = new MonetaryAmount();
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
     * Sets baseAmount.
     *
     * @param MonetaryAmount $baseAmount
     *
     * @return $this
     */
    public function setBaseAmount(MonetaryAmount $baseAmount)
    {
        $this->baseAmount = $baseAmount;

        return $this;
    }

    /**
     * Gets baseAmount.
     *
     * @return MonetaryAmount
     */
    public function getBaseAmount(): MonetaryAmount
    {
        return $this->baseAmount;
    }

    /**
     * Sets defaultRate.
     *
     * @param bool|null $defaultRate
     *
     * @return $this
     */
    public function setDefaultRate(?bool $defaultRate)
    {
        $this->defaultRate = $defaultRate;

        return $this;
    }

    /**
     * Gets defaultRate.
     *
     * @return bool|null
     */
    public function isDefaultRate(): ?bool
    {
        return $this->defaultRate;
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
     * Gets isBasedOn.
     *
     * @return self|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * Sets remark.
     *
     * @param string|null $remark
     *
     * @return $this
     */
    public function setRemark(?string $remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Gets remark.
     *
     * @return string|null
     */
    public function getRemark(): ?string
    {
        return $this->remark;
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

    /**
     * Sets value.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setValue(string $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets value.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
