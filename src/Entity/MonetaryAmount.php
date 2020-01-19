<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A monetary value or range. This type can be used to describe an amount of money such as $50 USD, or a range as in describing a bank account being suitable for a balance between £1,000 and £1,000,000 GBP, or the value of a salary, etc. It is recommended to use [PriceSpecification](http://schema.org/PriceSpecification) Types to describe the price of an Offer, Invoice, etc.
 *
 * @see http://schema.org/MonetaryAmount
 *
 * @ORM\Embeddable
 */
class MonetaryAmount
{
    /**
     * @var string|null The currency in which the monetary amount is expressed (in 3-letter [ISO 4217](http://en.wikipedia.org/wiki/ISO_4217) format).
     *
     * @see http://schema.org/currency
     *
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    protected $currency;

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
     * @param string|null $currency
     */
    public function __construct(?string $value = null, ?string $currency = null)
    {
        $this->value = $value;
        $this->currency = $currency;
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
     * Gets value.
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
