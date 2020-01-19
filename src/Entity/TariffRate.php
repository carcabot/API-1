<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\ContractType;
use App\Enum\TariffRateStatus;
use App\Enum\TariffRateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The tariff rate.
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"tariff_rate_number"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"tariff_rate_read"}},
 *     "denormalization_context"={"groups"={"tariff_rate_write"}},
 *     "filters"={
 *         "tariff_rate.date",
 *         "tariff_rate.exists",
 *         "tariff_rate.json_search",
 *         "tariff_rate.order",
 *         "tariff_rate.search",
 *     },
 * })
 */
class TariffRate
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
     * @var string|null For bridge use.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bridgeId;

    /**
     * @var string|null Charge description of the tariff rate.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $chargeDescription;

    /**
     * @var Collection<Contract> A contract.
     *
     * @ORM\OneToMany(targetEntity="Contract", cascade={"persist"}, mappedBy="tariffRate")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $contracts;

    /**
     * @var string[] Contracts types the tariff rate will be applicable for.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $contractTypes;

    /**
     * @var bool|null Determines whether the tariff rate is customizable.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $customizable;

    /**
     * @var Collection<TariffDailyRate> A daily rate.
     *
     * @ORM\OneToMany(targetEntity="TariffDailyRate", cascade={"persist"}, mappedBy="tariffRate")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $dailyRates;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var bool|null Determines whether the tariff rate is for internal use only.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $internalUseOnly;

    /**
     * @var QuantitativeValue The current approximate inventory level for the item or items.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty(iri="http://schema.org/inventoryLevel")
     */
    protected $inventoryLevel;

    /**
     * @var TariffRate|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="TariffRate")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var bool|null Determines whether the tariff rate is based on daily rate.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $isDailyRate;

    /**
     * @var QuantitativeValue The minimum contract term.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $minContractTerm;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var WebPage|null The tariff rate web page.
     *
     * @ORM\OneToOne(targetEntity="WebPage")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $page;

    /**
     * @var Collection<Promotion> The promotions applied to this tariff rate.
     *
     * @ORM\ManyToMany(targetEntity="Promotion", inversedBy="tariffRates", cascade={"persist"})
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $promotions;

    /**
     * @var string|null A remark.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $remark;

    /**
     * @var \DateTime|null The start date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/startDate")
     */
    protected $startDate;

    /**
     * @var TariffRateStatus The status of the tariff rate.
     *
     * @ORM\Column(type="tariff_rate_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var string The identifier of the tariffRate.
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     * @ApiProperty()
     */
    protected $tariffRateNumber;

    /**
     * @var TariffRateTerms|null The tariff rate terms.
     *
     * @ORM\OneToOne(targetEntity="TariffRateTerms", inversedBy="tariffRate", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $terms;

    /**
     * @var TariffRateType The type of the tariff rate.
     *
     * @ORM\Column(type="tariff_rate_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var string[] Where the tariff rate will be used.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $usedIn;

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
        $this->contracts = new ArrayCollection();
        $this->contractTypes = [];
        $this->dailyRates = new ArrayCollection();
        $this->inventoryLevel = new QuantitativeValue();
        $this->minContractTerm = new QuantitativeValue();
        $this->promotions = new ArrayCollection();
        $this->type = new TariffRateType(TariffRateType::NORMAL);
        $this->usedIn = [];
    }

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
            $this->contracts = new ArrayCollection();
            $this->page = null;
            $this->promotions = new ArrayCollection();

            if (null !== $this->startDate) {
                $this->startDate = clone $this->startDate;
            }

            if (null !== $this->terms) {
                $terms = clone $this->terms;
                $terms->setTariffRate($this);
            }

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
     * Sets bridgeId.
     *
     * @param string|null $bridgeId
     *
     * @return $this
     */
    public function setBridgeId(?string $bridgeId)
    {
        $this->bridgeId = $bridgeId;

        return $this;
    }

    /**
     * Gets bridgeId.
     *
     * @return string|null
     */
    public function getBridgeId(): ?string
    {
        return $this->bridgeId;
    }

    /**
     * Sets chargeDescription.
     *
     * @param string|null $chargeDescription
     *
     * @return $this
     */
    public function setChargeDescription(?string $chargeDescription)
    {
        $this->chargeDescription = $chargeDescription;

        return $this;
    }

    /**
     * Gets chargeDescription.
     *
     * @return string|null
     */
    public function getChargeDescription(): ?string
    {
        return $this->chargeDescription;
    }

    /**
     * Adds contract.
     *
     * @param Contract $contract
     *
     * @return $this
     */
    public function addContract(Contract $contract)
    {
        $this->contracts[] = $contract;

        return $this;
    }

    /**
     * Removes contract.
     *
     * @param Contract $contract
     *
     * @return $this
     */
    public function removeContract(Contract $contract)
    {
        $this->contracts->removeElement($contract);

        return $this;
    }

    /**
     * Gets contracts.
     *
     * @return Contract[]
     */
    public function getContracts(): array
    {
        return $this->contracts->getValues();
    }

    /**
     * Adds contractType.
     *
     * @param string $contractType
     *
     * @return $this
     */
    public function addContractType(string $contractType)
    {
        $this->contractTypes[] = $contractType;

        return $this;
    }

    /**
     * Removes contractType.
     *
     * @param string $contractType
     *
     * @return $this
     */
    public function removeContractType(string $contractType)
    {
        if (false !== ($key = \array_search($contractType, $this->contractTypes, true))) {
            \array_splice($this->contractTypes, $key, 1);
        }

        return $this;
    }

    /**
     * Clears all contractTypes.
     *
     * @return $this
     */
    public function clearContractTypes()
    {
        $this->contractTypes = [];

        return $this;
    }

    /**
     * Gets contractTypes.
     *
     * @return string[]
     */
    public function getContractTypes(): array
    {
        return $this->contractTypes;
    }

    /**
     * Sets customizable.
     *
     * @param bool|null $customizable
     *
     * @return $this
     */
    public function setCustomizable(?bool $customizable)
    {
        $this->customizable = $customizable;

        return $this;
    }

    /**
     * Gets customizable.
     *
     * @return bool|null
     */
    public function isCustomizable(): ?bool
    {
        return $this->customizable;
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
     * Sets internalUseOnly.
     *
     * @param bool|null $internalUseOnly
     *
     * @return $this
     */
    public function setInternalUseOnly(?bool $internalUseOnly)
    {
        $this->internalUseOnly = $internalUseOnly;

        return $this;
    }

    /**
     * Gets internalUseOnly.
     *
     * @return bool|null
     */
    public function isInternalUseOnly(): ?bool
    {
        return $this->internalUseOnly;
    }

    /**
     * Sets inventoryLevel.
     *
     * @param QuantitativeValue $inventoryLevel
     *
     * @return $this
     */
    public function setInventoryLevel(QuantitativeValue $inventoryLevel)
    {
        $this->inventoryLevel = $inventoryLevel;

        return $this;
    }

    /**
     * Gets inventoryLevel.
     *
     * @return QuantitativeValue
     */
    public function getInventoryLevel(): QuantitativeValue
    {
        return $this->inventoryLevel;
    }

    /**
     * Sets isBasedOn.
     *
     * @param TariffRate|null $isBasedOn
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
     * @return TariffRate|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * @return bool|null
     */
    public function getIsDailyRate(): ?bool
    {
        return $this->isDailyRate;
    }

    /**
     * @param bool|null $isDailyRate
     *
     * @return $this
     */
    public function setIsDailyRate(?bool $isDailyRate)
    {
        $this->isDailyRate = $isDailyRate;

        return $this;
    }

    /**
     * Sets minContractTerm.
     *
     * @param QuantitativeValue $minContractTerm
     *
     * @return $this
     */
    public function setMinContractTerm(QuantitativeValue $minContractTerm)
    {
        $this->minContractTerm = $minContractTerm;

        return $this;
    }

    /**
     * Gets minContractTerm.
     *
     * @return QuantitativeValue
     */
    public function getMinContractTerm(): QuantitativeValue
    {
        return $this->minContractTerm;
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
     * Sets page.
     *
     * @param WebPage|null $page
     *
     * @return $this
     */
    public function setPage(?WebPage $page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Gets page.
     *
     * @return WebPage|null
     */
    public function getPage(): ?WebPage
    {
        return $this->page;
    }

    /**
     * Adds promotion.
     *
     * @param Promotion $promotion
     *
     * @return $this
     */
    public function addPromotion(Promotion $promotion)
    {
        $this->promotions[] = $promotion;

        return $this;
    }

    /**
     * Removes promotion.
     *
     * @param Promotion $promotion
     *
     * @return $this
     */
    public function removePromotion(Promotion $promotion)
    {
        $this->promotions->removeElement($promotion);

        return $this;
    }

    /**
     * Gets promotions.
     *
     * @return Promotion[]
     */
    public function getPromotions(): array
    {
        if (null === $this->promotions) {
            $this->promotions = new ArrayCollection();
        }

        return $this->promotions->getValues();
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
     * Sets startDate.
     *
     * @param \DateTime|null $startDate
     *
     * @return $this
     */
    public function setStartDate(?\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Gets startDate.
     *
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * Sets status.
     *
     * @param TariffRateStatus $status
     *
     * @return $this
     */
    public function setStatus(TariffRateStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return TariffRateStatus
     */
    public function getStatus(): TariffRateStatus
    {
        return $this->status;
    }

    /**
     * Adds dailyRates.
     *
     * @param TariffDailyRate $dailyRate
     *
     * @return $this
     */
    public function addDailyRate(TariffDailyRate $dailyRate)
    {
        $this->dailyRates[] = $dailyRate;

        return $this;
    }

    /**
     * Removes dailyRates.
     *
     * @param TariffDailyRate $dailyRate
     *
     * @return $this
     */
    public function removeDailyRate(TariffDailyRate $dailyRate)
    {
        $this->dailyRates->removeElement($dailyRate);

        return $this;
    }

    /**
     * Removes all dailyRates.
     *
     * @return $this
     */
    public function removeAllDailyRates()
    {
        $this->dailyRates = new ArrayCollection();

        return $this;
    }

    /**
     * Gets dailyRates.
     *
     * @return TariffDailyRate[]
     */
    public function getDailyRates(): array
    {
        return $this->dailyRates->getValues();
    }

    /**
     * Sets tariffRateNumber.
     *
     * @param string $tariffRateNumber
     *
     * @return $this
     */
    public function setTariffRateNumber(string $tariffRateNumber)
    {
        $this->tariffRateNumber = $tariffRateNumber;

        return $this;
    }

    /**
     * Gets tariffRateNumber.
     *
     * @return string
     */
    public function getTariffRateNumber(): string
    {
        return $this->tariffRateNumber;
    }

    /**
     * Sets terms.
     *
     * @param TariffRateTerms|null $terms
     *
     * @return $this
     */
    public function setTerms(?TariffRateTerms $terms)
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * Gets terms.
     *
     * @return TariffRateTerms|null
     */
    public function getTerms(): ?TariffRateTerms
    {
        return $this->terms;
    }

    /**
     * Sets type.
     *
     * @param TariffRateType $type
     *
     * @return $this
     */
    public function setType(TariffRateType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return TariffRateType
     */
    public function getType(): TariffRateType
    {
        return $this->type;
    }

    /**
     * Adds usedIn.
     *
     * @param string $usedIn
     *
     * @return $this
     */
    public function addUsedIn(string $usedIn)
    {
        $this->usedIn[] = $usedIn;

        return $this;
    }

    /**
     * Removes usedIn.
     *
     * @param string $usedIn
     *
     * @return $this
     */
    public function removeUsedIn(string $usedIn)
    {
        if (false !== ($key = \array_search($usedIn, $this->usedIn, true))) {
            \array_splice($this->usedIn, $key, 1);
        }

        return $this;
    }

    /**
     * Clears usedIn.
     *
     * @return $this
     */
    public function clearUsedIn()
    {
        $this->usedIn = [];

        return $this;
    }

    /**
     * Gets usedIn.
     *
     * @return string[]
     */
    public function getUsedIn(): array
    {
        return $this->usedIn;
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
