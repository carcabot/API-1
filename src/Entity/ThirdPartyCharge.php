<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\ThirdPartyChargeCategory;
use Doctrine\ORM\Mapping as ORM;

/**
 * The third party charges.
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"date_created"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"third_party_charge_read"}},
 *     "denormalization_context"={"groups"={"third_party_charge_write"}},
 * })
 */
class ThirdPartyCharge
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
     * @var ThirdPartyChargeCategory|null The category of the third party charge.
     *
     * @ORM\Column(type="third_party_charge_enum", nullable=true)
     * @ApiProperty()
     */
    protected $category;

    /**
     * @var ThirdPartyChargeConfiguration|null The configuration this charge belongs to.
     *
     * @ORM\ManyToOne(targetEntity="ThirdPartyChargeConfiguration", inversedBy="charges")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     * @ApiProperty()
     */
    protected $configuration;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var bool|null The enabled indicator.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $enabled;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var string[] The price plan types where applicable.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $planTypes;

    /**
     * @var QuantitativeValue The rate.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $rate;

    /**
     * @var string|null The charge rate description.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $rateDescription;

    /**
     * @var string The identifier of the third party charge.
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     * @ApiProperty()
     */
    protected $thirdPartyChargeNumber;

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
        $this->planTypes = [];
        $this->rate = new QuantitativeValue();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ThirdPartyChargeCategory|null
     */
    public function getCategory(): ?ThirdPartyChargeCategory
    {
        return $this->category;
    }

    /**
     * @param ThirdPartyChargeCategory|null $category
     *
     * @return $this
     */
    public function setCategory(?ThirdPartyChargeCategory $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return ThirdPartyChargeConfiguration|null
     */
    public function getConfiguration(): ?ThirdPartyChargeConfiguration
    {
        return $this->configuration;
    }

    /**
     * @param ThirdPartyChargeConfiguration|null $configuration
     *
     * @return $this
     */
    public function setConfiguration(?ThirdPartyChargeConfiguration $configuration)
    {
        $this->configuration = $configuration;

        return $this;
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
     * @return bool|null
     */
    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled(?bool $enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Adds planType.
     *
     * @param string $planType
     *
     * @return $this
     */
    public function addPlanType(string $planType)
    {
        $this->planTypes[] = $planType;

        return $this;
    }

    /**
     * Removes planType.
     *
     * @param string $planType
     *
     * @return $this
     */
    public function removePlanType(string $planType)
    {
        if (false !== ($key = \array_search($planType, $this->planTypes, true))) {
            \array_splice($this->planTypes, $key, 1);
        }

        return $this;
    }

    /**
     * Gets planTypes.
     *
     * @return string[]
     */
    public function getPlanTypes(): array
    {
        return $this->planTypes;
    }

    /**
     * Sets rate.
     *
     * @param QuantitativeValue $rate
     *
     * @return $this
     */
    public function setRate(QuantitativeValue $rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Gets rate.
     *
     * @return QuantitativeValue
     */
    public function getRate(): QuantitativeValue
    {
        return $this->rate;
    }

    /**
     * @return string|null
     */
    public function getRateDescription(): ?string
    {
        return $this->rateDescription;
    }

    /**
     * @param string|null $rateDescription
     */
    public function setRateDescription(?string $rateDescription)
    {
        $this->rateDescription = $rateDescription;
    }

    /**
     * @return string
     */
    public function getThirdPartyChargeNumber(): string
    {
        return $this->thirdPartyChargeNumber;
    }

    /**
     * @param string $thirdPartyChargeNumber
     *
     * @return $this
     */
    public function setThirdPartyChargeNumber(string $thirdPartyChargeNumber)
    {
        $this->thirdPartyChargeNumber = $thirdPartyChargeNumber;

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
