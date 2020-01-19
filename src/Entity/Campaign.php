<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\CampaignCategory;
use App\Enum\CampaignPriority;
use App\Enum\CampaignStage;
use App\Enum\CampaignStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A Campaign.
 *
 * @ORM\Entity()
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"campaign_read"}},
 *     "denormalization_context"={"groups"={"campaign_write"}},
 *     "filters"={
 *         "campaign.order",
 *         "campaign.range",
 *         "campaign.search",
 *     },
 * })
 */
class Campaign
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string|null The subject matter of the content.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/about")
     */
    protected $about;

    /**
     * @var MonetaryAmount The actual cost of campaign.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $actualCost;

    /**
     * @var User|null The user/employee assigned to the campaign.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $assignee;

    /**
     * @var string The identifier of the campaign.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=false)
     * @ApiProperty()
     */
    protected $campaignNumber;

    /**
     * @var CampaignCategory Campaign category.
     *
     * @ORM\Column(type="campaign_category_enum", nullable=false)
     * @ApiProperty()
     */
    protected $category;

    /**
     * @var CampaignStage|null Campaign current stage.
     *
     * @ORM\Column(type="campaign_stage_enum", nullable=true)
     * @ApiProperty()
     */
    protected $currentStage;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var \DateTime|null The end date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/endDate")
     */
    protected $endDate;

    /**
     * @var MonetaryAmount The estimated cost of the supply or supplies consumed when performing instructions.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty(iri="http://schema.org/estimatedCost")
     */
    protected $estimatedCost;

    /**
     * @var CampaignExpectationList|null Campaign expectations.
     *
     * @ORM\OneToOne(targetEntity="CampaignExpectationList")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $expectationList;

    /**
     * @var InternalDocument|null A softcopy of the campaign to be printed.
     *
     * @ORM\OneToOne(targetEntity="InternalDocument")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $file;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var string|null Campaign note.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $note;

    /**
     * @var CampaignPriority|null Priority of campaign.
     *
     * @ORM\Column(type="campaign_priority_enum", nullable=true)
     * @ApiProperty()
     */
    protected $priority;

    /**
     * @var string|null Objective of campaign.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $objective;

    /**
     * @var \DateTime|null Planned end date of campaign.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $plannedEndDate;

    /**
     * @var \DateTime|null Planned start date of campaign.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $plannedStartDate;

    /**
     * @var Collection<SourceList> Campaign sources.
     *
     * @ORM\ManyToMany(targetEntity="SourceList", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="campaign_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="source_list_id", onDelete="CASCADE")},
     * )
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $recipientLists;

    /**
     * @var \DateTime|null The start date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/startDate")
     */
    protected $startDate;

    /**
     * @var CampaignStatus Status of campaign.
     *
     * @ORM\Column(type="campaign_status_enum")
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var TariffRate|null The tariff rate used in the campaign.
     *
     * @ORM\ManyToOne(targetEntity="TariffRate")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $tariffRate;

    /**
     * @var CampaignTemplate|null Campaign template.
     *
     * @ORM\OneToOne(targetEntity="CampaignTemplate")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $template;

    public function __construct()
    {
        $this->actualCost = new MonetaryAmount();
        $this->estimatedCost = new MonetaryAmount();
        $this->recipientLists = new ArrayCollection();
    }

    /**
     * Gets campaign id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets about.
     *
     * @return string|null
     */
    public function getAbout(): ?string
    {
        return $this->about;
    }

    /**
     * Sets about.
     *
     * @param string|null $about
     */
    public function setAbout(?string $about): void
    {
        $this->about = $about;
    }

    /**
     * Gets actual cost.
     *
     * @return MonetaryAmount
     */
    public function getActualCost(): MonetaryAmount
    {
        return $this->actualCost;
    }

    /**
     * Sets actual cost.
     *
     * @param MonetaryAmount $actualCost
     */
    public function setActualCost(MonetaryAmount $actualCost): void
    {
        $this->actualCost = $actualCost;
    }

    /**
     * Gets assignee.
     *
     * @return User|null
     */
    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    /**
     * Sets assignee.
     *
     * @param User|null $assignee
     */
    public function setAssignee(?User $assignee): void
    {
        $this->assignee = $assignee;
    }

    /**
     * Gets campaign number.
     *
     * @return string
     */
    public function getCampaignNumber(): string
    {
        return $this->campaignNumber;
    }

    /**
     * Sets campaign number.
     *
     * @param string $campaignNumber
     */
    public function setCampaignNumber(string $campaignNumber): void
    {
        $this->campaignNumber = $campaignNumber;
    }

    /**
     * Gets campaign category.
     *
     * @return CampaignCategory
     */
    public function getCategory(): CampaignCategory
    {
        return $this->category;
    }

    /**
     * Sets campaign category.
     *
     * @param CampaignCategory $category
     */
    public function setCategory(CampaignCategory $category): void
    {
        $this->category = $category;
    }

    /**
     * Gets campaign stage.
     *
     * @return CampaignStage|null
     */
    public function getCurrentStage(): ?CampaignStage
    {
        return $this->currentStage;
    }

    /**
     * Sets campaign stage.
     *
     * @param CampaignStage|null $currentStage
     */
    public function setCurrentStage(?CampaignStage $currentStage): void
    {
        $this->currentStage = $currentStage;
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
     * Sets description.
     *
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Gets end date.
     *
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * Sets end date.
     *
     * @param \DateTime|null $endDate
     */
    public function setEndDate(?\DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * Gets estimated cost.
     *
     * @return MonetaryAmount
     */
    public function getEstimatedCost(): MonetaryAmount
    {
        return $this->estimatedCost;
    }

    /**
     * Sets estimated cost.
     *
     * @param MonetaryAmount $estimatedCost
     */
    public function setEstimatedCost(MonetaryAmount $estimatedCost): void
    {
        $this->estimatedCost = $estimatedCost;
    }

    /**
     * Get expectation list.
     *
     * @return CampaignExpectationList|null
     */
    public function getExpectationList(): ?CampaignExpectationList
    {
        return $this->expectationList;
    }

    /**
     * Sets expectation list.
     *
     * @param CampaignExpectationList|null $expectationList
     */
    public function setExpectationList(?CampaignExpectationList $expectationList): void
    {
        $this->expectationList = $expectationList;
    }

    /**
     * Sets file.
     *
     * @param InternalDocument|null $file
     *
     * @return $this
     */
    public function setFile(?InternalDocument $file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Gets file.
     *
     * @return InternalDocument|null
     */
    public function getFile(): ?InternalDocument
    {
        return $this->file;
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
     * Sets name.
     *
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets note.
     *
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * Sets note.
     *
     * @param string|null $note
     */
    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    /**
     * Gets campaign priority.
     *
     * @return CampaignPriority|null
     */
    public function getPriority(): ?CampaignPriority
    {
        return $this->priority;
    }

    /**
     * Sets campaign priority.
     *
     * @param CampaignPriority|null $priority
     */
    public function setPriority(?CampaignPriority $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Gets campaign objective.
     *
     * @return string|null
     */
    public function getObjective(): ?string
    {
        return $this->objective;
    }

    /**
     * Sets campaign objective.
     *
     * @param string|null $objective
     */
    public function setObjective(?string $objective): void
    {
        $this->objective = $objective;
    }

    /**
     * Gets planned end date.
     *
     * @return \DateTime|null
     */
    public function getPlannedEndDate(): ?\DateTime
    {
        return $this->plannedEndDate;
    }

    /**
     * Sets planned end date.
     *
     * @param \DateTime|null $plannedEndDate
     */
    public function setPlannedEndDate(?\DateTime $plannedEndDate): void
    {
        $this->plannedEndDate = $plannedEndDate;
    }

    /**
     * Gets planned start date.
     *
     * @return \DateTime|null
     */
    public function getPlannedStartDate(): ?\DateTime
    {
        return $this->plannedStartDate;
    }

    /**
     * Sets planned start date.
     *
     * @param \DateTime|null $plannedStartDate
     */
    public function setPlannedStartDate(?\DateTime $plannedStartDate): void
    {
        $this->plannedStartDate = $plannedStartDate;
    }

    /**
     * Gets start date.
     *
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * Sets start date.
     *
     * @param \DateTime|null $startDate
     */
    public function setStartDate(?\DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * Gets campaign status.
     *
     * @return CampaignStatus
     */
    public function getStatus(): CampaignStatus
    {
        return $this->status;
    }

    /**
     * Sets campaign status.
     *
     * @param CampaignStatus $status
     */
    public function setStatus(CampaignStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * Gets tariff rate.
     *
     * @return TariffRate|null
     */
    public function getTariffRate(): ?TariffRate
    {
        return $this->tariffRate;
    }

    /**
     * Sets tariff rate.
     *
     * @param TariffRate|null $tariffRate
     */
    public function setTariffRate(?TariffRate $tariffRate): void
    {
        $this->tariffRate = $tariffRate;
    }

    /**
     * Gets template.
     *
     * @return CampaignTemplate|null
     */
    public function getTemplate(): ?CampaignTemplate
    {
        return $this->template;
    }

    /**
     * Sets template.
     *
     * @param CampaignTemplate|null $template
     */
    public function setTemplate(?CampaignTemplate $template): void
    {
        $this->template = $template;
    }

    /**
     * Gets recipientLists.
     *
     * @return SourceList[]
     */
    public function getRecipientLists(): array
    {
        return $this->recipientLists->getValues();
    }

    /**
     * Add recipientList.
     *
     * @param SourceList $recipientList
     */
    public function addRecipientList(SourceList $recipientList): void
    {
        $this->recipientLists[] = $recipientList;
    }

    /**
     * Removes recipientList.
     *
     * @param SourceList $recipientList
     */
    public function removeRecipientList(SourceList $recipientList): void
    {
        $this->recipientLists->removeElement($recipientList);
    }
}
