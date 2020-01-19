<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\WebService\Billing\Controller\ThirdPartyChargeConfigurationController;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The third party charge configuration.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"third_party_charge_configuration_read"}},
 *     "denormalization_context"={"groups"={"third_party_charge_configuration_write"}},
 *     "filters"={
 *         "third_party_charge_configuration.date",
 *         "third_party_charge_configuration.exists",
 *         "third_party_charge_configuration.order",
 *         "third_party_charge_configuration.search",
 *     },
 * },
 * collectionOperations={
 *     "post",
 *     "get"={
 *         "method"="GET",
 *         "path"="/third_party_charge_configurations.{_format}",
 *         "controller"=ThirdPartyChargeConfigurationController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"third_party_charge_configuration_read"}},
 *     },
 * })
 */
class ThirdPartyChargeConfiguration
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
     * @var Collection<ThirdPartyCharge> The third party charges.
     *
     * @ORM\OneToMany(targetEntity="ThirdPartyCharge", cascade={"persist"}, mappedBy="configuration", orphanRemoval=true)
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $charges;

    /**
     * @var string The identifier of the third party charge configuration.
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     * @ApiProperty()
     */
    protected $configurationNumber;

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
     * @var ThirdPartyChargeConfiguration|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="ThirdPartyChargeConfiguration")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

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
        $this->charges = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Adds charge.
     *
     * @param ThirdPartyCharge $charge
     *
     * @return $this
     */
    public function addCharge(ThirdPartyCharge $charge)
    {
        $this->charges[] = $charge;
        $charge->setConfiguration($this);

        return $this;
    }

    /**
     * Clears charges.
     *
     * @return $this
     */
    public function clearCharges()
    {
        $this->charges = new ArrayCollection();

        return $this;
    }

    /**
     * Removes charge.
     *
     * @param ThirdPartyCharge $charge
     *
     * @return $this
     */
    public function removeCharge(ThirdPartyCharge $charge)
    {
        $this->charges->removeElement($charge);

        return $this;
    }

    /**
     * Gets charges.
     *
     * @return ThirdPartyCharge[]
     */
    public function getCharges(): array
    {
        return $this->charges->getValues();
    }

    /**
     * @return string
     */
    public function getConfigurationNumber(): string
    {
        return $this->configurationNumber;
    }

    /**
     * @param string $configurationNumber
     *
     * @return $this
     */
    public function setConfigurationNumber(string $configurationNumber)
    {
        $this->configurationNumber = $configurationNumber;

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
     * Sets isBasedOn.
     *
     * @param ThirdPartyChargeConfiguration|null $isBasedOn
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
     * @return ThirdPartyChargeConfiguration|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * Sets name.
     *
     * @param string|null $name
     *
     * @return $this
     */
    public function setName(?string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
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
