<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\AffiliateWebServicePartner;
use App\Enum\CommissionAllocation;
use App\Enum\CommissionType;
use Doctrine\ORM\Mapping as ORM;

/**
 * The affiliate program commission configuration settings.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"affiliate_program_commission_configuration_read"}},
 *     "denormalization_context"={"groups"={"affiliate_program_commission_configuration_write"}},
 *     "filters"={
 *         "affiliate_program_commission_configuration.exists",
 *         "affiliate_program_commission_configuration.order"
 *     },
 * })
 */
class AffiliateProgramCommissionConfiguration
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
     * @var CommissionAllocation The commission allocation type.
     *
     * @ORM\Column(type="commission_allocation_enum", nullable=false)
     * @ApiProperty()
     */
    protected $allocationType;

    /**
     * @var string|null The currency used for calculation.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty()
     */
    protected $currency;

    /**
     * @var AffiliateProgramCommissionConfiguration|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="AffiliateProgramCommissionConfiguration")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var AffiliateWebServicePartner|null The service provider, service operator, or service performer; the goods producer. Another party (a seller) may offer those services or goods on behalf of the provider. A provider may also serve as the seller.
     *
     * @ORM\Column(type="affiliate_web_service_partner_enum", nullable=true)
     * @ApiProperty(iri="http://schema.org/provider")
     */
    protected $provider;

    /**
     * @var CommissionType The commission type.
     *
     * @ORM\Column(type="commission_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var string The value of the quantitative value or property value node.
     *
     * - For [QuantitativeValue](http://schema.org/QuantitativeValue) and [MonetaryAmount](http://schema.org/MonetaryAmount, the recommended type for values is 'Number'.
     * - For [PropertyValue](http://schema.org/PropertyValue), it can be 'Text;', 'Number', 'Boolean', or 'StructuredValue'.
     *
     * @ORM\Column(type="string", nullable=false)
     * @ApiProperty(iri="http://schema.org/value")
     */
    protected $value;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
            $this->isBasedOn = null;
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
     * Sets allocationType.
     *
     * @param CommissionAllocation $allocationType
     *
     * @return $this
     */
    public function setAllocationType(CommissionAllocation $allocationType)
    {
        $this->allocationType = $allocationType;

        return $this;
    }

    /**
     * Gets allocationType.
     *
     * @return CommissionAllocation
     */
    public function getAllocationType(): CommissionAllocation
    {
        return $this->allocationType;
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
     * Sets provider.
     *
     * @param AffiliateWebServicePartner|null $provider
     *
     * @return $this
     */
    public function setProvider(?AffiliateWebServicePartner $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Gets provider.
     *
     * @return AffiliateWebServicePartner|null
     */
    public function getProvider(): ?AffiliateWebServicePartner
    {
        return $this->provider;
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
