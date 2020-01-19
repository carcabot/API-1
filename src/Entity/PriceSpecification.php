<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A structured value representing a price or price range. Typically, only the subclasses of this type are used for markup. It is recommended to use MonetaryAmount to describe independent amounts of money such as a salary, credit card limits, etc.
 *
 * @see http://schema.org/PriceSpecification
 *
 * @ORM\Embeddable
 */
class PriceSpecification
{
    /**
     * @var string|null The highest price if the price is a range.
     *
     * @see http://schema.org/maxPrice
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     */
    protected $maxPrice;

    /**
     * @var string|null The lowest price if the price is a range.
     *
     * @see http://schema.org/minPrice
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     */
    protected $minPrice;

    /**
     * @var string|null The offer price of a product, or of a price component when attached to PriceSpecification and its subtypes.
     *
     * Usage guidelines:
     *
     * - Use the [priceCurrency](http://schema.org/priceCurrency) property (with [ISO 4217 codes](http://en.wikipedia.org/wiki/ISO_4217#Active_codes) e.g. "USD") instead of including [ambiguous symbols](http://en.wikipedia.org/wiki/Dollar_sign#Currencies_that_use_the_dollar_or_peso_sign) such as '$' in the value.
     * - Use '.' (Unicode 'FULL STOP' (U+002E)) rather than ',' to indicate a decimal point. Avoid using these symbols as a readability separator.
     * - Note that both [RDFa](http://www.w3.org/TR/xhtml-rdfa-primer/#using-the-content-attribute) and Microdata syntax allow the use of a "content=" attribute for publishing simple machine-readable values alongside more human-friendly formatting.
     * - Use values from 0123456789 (Unicode 'DIGIT ZERO' (U+0030) to 'DIGIT NINE' (U+0039)) rather than superficially similiar Unicode symbols.
     *
     * @see http://schema.org/price
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     */
    protected $price;

    /**
     * @var string|null The currency of the price, or a price component when attached to PriceSpecification and its subtypes.
     *
     * - Use standard formats: ISO 4217 currency format e.g. "USD"; Ticker symbol for cryptocurrencies e.g. "BTC"; well known names for Local Exchange Tradings Systems (LETS) and other currency types e.g. "Ithaca HOUR".
     *
     * @see http://schema.org/priceCurrency
     *
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    protected $priceCurrency;

    /**
     * @param string|null $maxPrice
     * @param string|null $minPrice
     * @param string|null $price
     * @param string|null $priceCurrency
     */
    public function __construct(?string $maxPrice = null, ?string $minPrice = null, ?string $price = null, ?string $priceCurrency = null)
    {
        $this->maxPrice = $maxPrice;
        $this->minPrice = $minPrice;
        $this->price = $price;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Gets maxPrice.
     *
     * @return string|null
     */
    public function getMaxPrice(): ?string
    {
        return $this->maxPrice;
    }

    /**
     * Gets minPrice.
     *
     * @return string|null
     */
    public function getMinPrice(): ?string
    {
        return $this->minPrice;
    }

    /**
     * Gets price.
     *
     * @return string|null
     */
    public function getPrice(): ?string
    {
        return $this->price;
    }

    /**
     * Gets priceCurrency.
     *
     * @return string|null
     */
    public function getPriceCurrency(): ?string
    {
        return $this->priceCurrency;
    }
}
