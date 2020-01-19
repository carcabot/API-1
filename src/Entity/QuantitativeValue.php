<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A point value or interval for product characteristics and other purposes.
 *
 * @see http://schema.org/QuantitativeValue
 *
 * @ORM\Embeddable
 */
class QuantitativeValue implements \JsonSerializable
{
    /**
     * @var string|null The upper value of some characteristic or property.
     *
     * @see http://schema.org/maxValue
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     */
    protected $maxValue;

    /**
     * @var string|null The lower value of some characteristic or property.
     *
     * @see http://schema.org/minValue
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     */
    protected $minValue;

    /**
     * @var string|null The unit of measurement given using the UN/CEFACT Common Code (3 characters) or a URL. Other codes than the UN/CEFACT Common Code may be used with a prefix followed by a colon.
     *
     * @see http://schema.org/unitCode
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $unitCode;

    /**
     * @var string|null The value of the quantitative value or property value node.
     *
     * - For [QuantitativeValue](http://schema.org/QuantitativeValue) and [MonetaryAmount](http://schema.org/MonetaryAmount, the recommended type for values is 'Number'.
     * - For [PropertyValue](http://schema.org/PropertyValue), it can be 'Text;', 'Number', 'Boolean', or 'StructuredValue'.
     *
     * @see http://schema.org/value
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     */
    protected $value;

    /**
     * @param string|null $value
     * @param string|null $minValue
     * @param string|null $maxValue
     * @param string|null $unitCode
     */
    public function __construct(?string $value = null, ?string $minValue = null, ?string $maxValue = null, ?string $unitCode = null)
    {
        $this->value = $value;
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
        $this->unitCode = $unitCode;
    }

    /**
     * Gets maxValue.
     *
     * @return string|null
     */
    public function getMaxValue(): ?string
    {
        return $this->maxValue;
    }

    /**
     * Gets minValue.
     *
     * @return string|null
     */
    public function getMinValue(): ?string
    {
        return $this->minValue;
    }

    /**
     * Gets value.
     *
     * @return string|null
     */
    public function getUnitCode(): ?string
    {
        return $this->unitCode;
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
     * @return array
     */
    public function jsonSerialize()
    {
        $vars = \get_object_vars($this);

        return $vars;
    }
}
