<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The price configuration.
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"date_created"}),
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *     "price_configuration"="PriceConfiguration",
 *     "quotation_price_configuration"="QuotationPriceConfiguration",
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"price_configuration_read"}},
 *     "denormalization_context"={"groups"={"price_configuration_write"}},
 * })
 */
class PriceConfiguration
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
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var bool The enabled indicator.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @ApiProperty()
     */
    protected $enabled;

    /**
     * @var string The name of the third party charge.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var QuantitativeValue The duration applicable for the offer.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $offerDuration;

    /**
     * @var PriceSpecification The offered rate.
     *
     * @ORM\Embedded(class="PriceSpecification")
     * @ApiProperty()
     */
    protected $rate;

    public function __construct()
    {
        $this->offerDuration = new QuantitativeValue();
        $this->rate = new PriceSpecification();
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
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
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
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
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
     * @param QuantitativeValue $offerDuration
     *
     * @return $this
     */
    public function setOfferDuration(QuantitativeValue $offerDuration)
    {
        $this->offerDuration = $offerDuration;

        return $this;
    }

    /**
     * @return QuantitativeValue
     */
    public function getOfferDuration(): QuantitativeValue
    {
        return $this->offerDuration;
    }

    /**
     * @return PriceSpecification
     */
    public function getRate(): PriceSpecification
    {
        return $this->rate;
    }

    /**
     * @param PriceSpecification $rate
     *
     * @return $this
     */
    public function setRate(PriceSpecification $rate)
    {
        $this->rate = $rate;

        return $this;
    }
}
