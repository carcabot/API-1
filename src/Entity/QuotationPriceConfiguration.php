<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\QuotationPricePlanType;
use Doctrine\ORM\Mapping as ORM;

/**
 * Quotation price configuration.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"quotation_price_configuration_read"}},
 *     "denormalization_context"={"groups"={"quotation_price_configuration_write"}},
 *     "filters"={
 *         "quotation_price_configuration.boolean",
 *         "quotation_price_configuration.date",
 *         "quotation_price_configuration.exists",
 *         "quotation_price_configuration.order",
 *         "quotation_price_configuration.search",
 *     },
 * })
 */
class QuotationPriceConfiguration extends PriceConfiguration
{
    /**
     * @var QuotationPricePlanType The price configuration category.
     *
     * @ORM\Column(type="quotation_price_plan_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $category;

    /**
     * @var ContractDuration|null The proposed duration for this QuotationPriceConfiguration
     *
     * @ORM\OneToOne(targetEntity="ContractDuration")
     * @ApiProperty()
     */
    protected $duration;

    /**
     * @var QuotationPriceConfiguration|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="QuotationPriceConfiguration")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var QuantitativeValue|null
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $offerInput;

    /**
     * @var QuantitativeValue The term of the quotation.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $term;

    /**
     * @var ThirdPartyChargeConfiguration|null The third party charge configuration of the quotation.
     *
     * @ORM\ManyToOne(targetEntity="ThirdPartyChargeConfiguration", cascade={"persist"})
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $thirdPartyChargeConfiguration;

    public function __construct()
    {
        parent::__construct();
        $this->term = new QuantitativeValue();
    }

    /**
     * @return QuotationPricePlanType
     */
    public function getCategory(): QuotationPricePlanType
    {
        return $this->category;
    }

    /**
     * @param QuotationPricePlanType $category
     *
     * @return $this
     */
    public function setCategory(QuotationPricePlanType $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return ContractDuration|null
     */
    public function getDuration(): ?ContractDuration
    {
        return $this->duration;
    }

    /**
     * @param ContractDuration|null $duration
     */
    public function setDuration(?ContractDuration $duration): void
    {
        $this->duration = $duration;
    }

    /**
     * @return QuotationPriceConfiguration|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * @param QuotationPriceConfiguration|null $isBasedOn
     */
    public function setIsBasedOn(?self $isBasedOn): void
    {
        $this->isBasedOn = $isBasedOn;
    }

    /**
     * @return QuantitativeValue|null
     */
    public function getOfferInput(): ?QuantitativeValue
    {
        return $this->offerInput;
    }

    /**
     * @param QuantitativeValue|null $offerInput
     */
    public function setOfferInput(?QuantitativeValue $offerInput): void
    {
        $this->offerInput = $offerInput;
    }

    /**
     * @return QuantitativeValue
     */
    public function getTerm(): QuantitativeValue
    {
        return $this->term;
    }

    /**
     * @param QuantitativeValue $term
     *
     * @return $this
     */
    public function setTerm(QuantitativeValue $term)
    {
        $this->term = $term;

        return $this;
    }

    /**
     * @return ThirdPartyChargeConfiguration|null
     */
    public function getThirdPartyChargeConfiguration(): ?ThirdPartyChargeConfiguration
    {
        return $this->thirdPartyChargeConfiguration;
    }

    /**
     * @param ThirdPartyChargeConfiguration|null $thirdPartyChargeConfiguration
     *
     * @return $this
     */
    public function setThirdPartyChargeConfiguration(?ThirdPartyChargeConfiguration $thirdPartyChargeConfiguration)
    {
        $this->thirdPartyChargeConfiguration = $thirdPartyChargeConfiguration;

        return $this;
    }
}
