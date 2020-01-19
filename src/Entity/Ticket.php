<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\Priority;
use App\Enum\TicketStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The ticket.
 *
 * @ORM\Entity(repositoryClass="App\Repository\TicketRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"keywords"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"ticket_read"}},
 *     "denormalization_context"={"groups"={"ticket_write"}},
 *     "filters"={
 *         "ticket.date",
 *         "ticket.exists",
 *         "ticket.range",
 *         "ticket.order",
 *         "ticket.search",
 *     },
 * })
 */
class Ticket
{
    use Traits\BlameableTrait;
    use Traits\SourceableTrait;
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
     * @var Collection<Activity> The activity carried out on an item.
     *
     * @ORM\ManyToMany(targetEntity="Activity", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="ticket_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="activity_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $activities;

    /**
     * @var User|null The user/employee assigned to the lead.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $assignee;

    /**
     * @var TicketCategory The category.
     *
     * @ORM\ManyToOne(targetEntity="TicketCategory")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/category")
     */
    protected $category;

    /**
     * @var string|null The channel.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $channel;

    /**
     * @var Contract|null The contract number.
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $contract;

    /**
     * @var CustomerAccount|null Party placing the order or paying the invoice.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/customer")
     */
    protected $customer;

    /**
     * @var \DateTime|null The date when the ticket is closed.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $dateClosed;

    /**
     * @var \DateTime|null The date when the ticket is opened.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $dateOpened;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var string|null
     *
     * @ORM\Column(type="tsvector", nullable=true, options={
     *     "tsvector_fields"={
     *         "ticketNumber"={
     *             "config"="english",
     *             "weight"="A",
     *         },
     *     },
     * })
     */
    protected $keywords;

    /**
     * @var Collection<Note> The note added for the item.
     *
     * @ORM\ManyToMany(targetEntity="Note", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="ticket_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="note_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $notes;

    /**
     * @var Ticket|null The parent of the item.
     *
     * @ORM\ManyToOne(targetEntity="Ticket")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $parent;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $paused;

    /**
     * @var Person|null The person details.
     *
     * @ORM\OneToOne(targetEntity="Person", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $personDetails;

    /**
     * @var \DateTime|null The planned completion date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $plannedCompletionDate;

    /**
     * @var Priority The priority of a service level agreement.
     *
     * @ORM\Column(type="priority_enum", nullable=false)
     * @ApiProperty()
     */
    protected $priority;

    /**
     * @var string|null The resolution officer.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $resolutionOfficer;

    /**
     * @var TicketServiceLevelAgreement|null The service level agreement.
     *
     * @ORM\OneToOne(targetEntity="TicketServiceLevelAgreement", inversedBy="ticket")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $serviceLevelAgreement;

    /**
     * @var Collection<ServiceLevelAgreementAction> A service level agreement action.
     *
     * @ORM\OneToMany(targetEntity="ServiceLevelAgreementAction", cascade={"persist"}, mappedBy="ticket")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $serviceLevelAgreementActions;

    /**
     * @var \DateTime The start date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty(iri="http://schema.org/startDate")
     */
    protected $startDate;

    /**
     * @var TicketStatus The ticket status.
     *
     * @ORM\Column(type="ticket_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var TicketCategory The subcategory.
     *
     * @ORM\ManyToOne(targetEntity="TicketCategory")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $subcategory;

    /**
     * @var Collection<DigitalDocument> A file attached to the ticket.
     *
     * @ORM\ManyToMany(targetEntity="DigitalDocument", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="ticket_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $supplementaryFiles;

    /**
     * @var string The unique identifier of the item.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=false)
     * @ApiProperty()
     */
    protected $ticketNumber;

    /**
     * @var QuantitativeValue
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $timeLeft;

    /**
     * @var QuantitativeValue
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $timer;

    /**
     * @var TicketType The item type.
     *
     * @ORM\ManyToOne(targetEntity="TicketType")
     * @ORM\JoinColumn(nullable=false, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $type;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->supplementaryFiles = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->serviceLevelAgreementActions = new ArrayCollection();
        $this->timer = new QuantitativeValue();
        $this->timeLeft = new QuantitativeValue();
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
     * Adds activities.
     *
     * @param Activity $activities
     *
     * @return $this
     */
    public function addActivity(Activity $activities)
    {
        $this->activities[] = $activities;

        return $this;
    }

    /**
     * Removes activities.
     *
     * @param Activity $activities
     *
     * @return $this
     */
    public function removeActivity(Activity $activities)
    {
        $this->activities->removeElement($activities);

        return $this;
    }

    /**
     * Gets activities.
     *
     * @return Activity[]
     */
    public function getActivities(): array
    {
        return $this->activities->getValues();
    }

    /**
     * Sets assignee.
     *
     * @param User|null $assignee
     *
     * @return $this
     */
    public function setAssignee(?User $assignee)
    {
        $this->assignee = $assignee;

        return $this;
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
     * Sets category.
     *
     * @param TicketCategory $category
     *
     * @return $this
     */
    public function setCategory(TicketCategory $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Gets category.
     *
     * @return TicketCategory
     */
    public function getCategory(): TicketCategory
    {
        return $this->category;
    }

    /**
     * @return string|null
     */
    public function getChannel(): ?string
    {
        return $this->channel;
    }

    /**
     * @param string|null $channel
     */
    public function setChannel(?string $channel): void
    {
        $this->channel = $channel;
    }

    /**
     * Sets contract.
     *
     * @param Contract|null $contract
     *
     * @return $this
     */
    public function setContract(?Contract $contract)
    {
        $this->contract = $contract;

        return $this;
    }

    /**
     * Gets contract.
     *
     * @return Contract|null
     */
    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    /**
     * Sets customer.
     *
     * @param CustomerAccount|null $customer
     *
     * @return $this
     */
    public function setCustomer(?CustomerAccount $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Gets customer.
     *
     * @return CustomerAccount|null
     */
    public function getCustomer(): ?CustomerAccount
    {
        return $this->customer;
    }

    /**
     * Sets dateClosed.
     *
     * @param \DateTime|null $dateClosed
     *
     * @return $this
     */
    public function setDateClosed(?\DateTime $dateClosed)
    {
        $this->dateClosed = $dateClosed;

        return $this;
    }

    /**
     * Gets dateClosed.
     *
     * @return \DateTime|null
     */
    public function getDateClosed(): ?\DateTime
    {
        return $this->dateClosed;
    }

    /**
     * Sets dateOpened.
     *
     * @param \DateTime|null $dateOpened
     *
     * @return $this
     */
    public function setDateOpened(?\DateTime $dateOpened)
    {
        $this->dateOpened = $dateOpened;

        return $this;
    }

    /**
     * Gets dateOpened.
     *
     * @return \DateTime|null
     */
    public function getDateOpened(): ?\DateTime
    {
        return $this->dateOpened;
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
     * Adds note.
     *
     * @param Note $note
     *
     * @return $this
     */
    public function addNote(Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * Removes note.
     *
     * @param Note $note
     *
     * @return $this
     */
    public function removeNote(Note $note)
    {
        $this->notes->removeElement($note);

        return $this;
    }

    /**
     * Gets notes.
     *
     * @return Note[]
     */
    public function getNotes(): array
    {
        return $this->notes->getValues();
    }

    /**
     * Sets parent.
     *
     * @param Ticket|null $parent
     *
     * @return $this
     */
    public function setParent(?self $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Gets parent.
     *
     * @return Ticket|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * Gets paused.
     *
     * @return bool|null
     */
    public function getPaused(): ?bool
    {
        return $this->paused;
    }

    /**
     * Sets paused.
     *
     * @param bool|null $paused
     *
     * @return $this
     */
    public function setPaused(?bool $paused)
    {
        $this->paused = $paused;

        return $this;
    }

    /**
     * Sets personDetails.
     *
     * @param Person|null $personDetails
     *
     * @return $this
     */
    public function setPersonDetails(?Person $personDetails)
    {
        $this->personDetails = $personDetails;

        return $this;
    }

    /**
     * Gets personDetails.
     *
     * @return Person|null
     */
    public function getPersonDetails(): ?Person
    {
        return $this->personDetails;
    }

    /**
     * Sets plannedCompletionDate.
     *
     * @param \DateTime|null $plannedCompletionDate
     *
     * @return $this
     */
    public function setPlannedCompletionDate(?\DateTime $plannedCompletionDate)
    {
        $this->plannedCompletionDate = $plannedCompletionDate;

        return $this;
    }

    /**
     * Gets plannedCompletionDate.
     *
     * @return \DateTime|null
     */
    public function getPlannedCompletionDate(): ?\DateTime
    {
        return $this->plannedCompletionDate;
    }

    /**
     * Sets priority.
     *
     * @param Priority $priority
     *
     * @return $this
     */
    public function setPriority(Priority $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Gets priority.
     *
     * @return Priority
     */
    public function getPriority(): Priority
    {
        return $this->priority;
    }

    /**
     * Sets resolutionOfficer.
     *
     * @param string|null $resolutionOfficer
     *
     * @return $this
     */
    public function setResolutionOfficer(?string $resolutionOfficer)
    {
        $this->resolutionOfficer = $resolutionOfficer;

        return $this;
    }

    /**
     * Gets resolutionOfficer.
     *
     * @return string|null
     */
    public function getResolutionOfficer(): ?string
    {
        return $this->resolutionOfficer;
    }

    /**
     * Sets serviceLevelAgreement.
     *
     * @param TicketServiceLevelAgreement|null $serviceLevelAgreement
     *
     * @return $this
     */
    public function setServiceLevelAgreement(?TicketServiceLevelAgreement $serviceLevelAgreement)
    {
        $this->serviceLevelAgreement = $serviceLevelAgreement;

        return $this;
    }

    /**
     * Gets serviceLevelAgreement.
     *
     * @return TicketServiceLevelAgreement|null
     */
    public function getServiceLevelAgreement(): ?TicketServiceLevelAgreement
    {
        return $this->serviceLevelAgreement;
    }

    /**
     * Add ServiceLevelAgreementAction.
     *
     * @param ServiceLevelAgreementAction $serviceLevelAgreementAction
     *
     * @return $this
     */
    public function addServiceLevelAgreementAction(ServiceLevelAgreementAction $serviceLevelAgreementAction)
    {
        $this->serviceLevelAgreementActions[] = $serviceLevelAgreementAction;

        return $this;
    }

    /**
     * Removes serviceLevelAgreementActions.
     *
     * @param ServiceLevelAgreementAction $serviceLevelAgreementAction
     *
     * @return $this
     */
    public function removeServiceLevelAgreementActions(ServiceLevelAgreementAction $serviceLevelAgreementAction)
    {
        $this->serviceLevelAgreementActions->removeElement($serviceLevelAgreementAction);

        return $this;
    }

    /**
     * Gets serviceLevelAgreementActions.
     *
     * @return ServiceLevelAgreementAction[]
     */
    public function getServiceLevelAgreementActions(): array
    {
        return $this->serviceLevelAgreementActions->getValues();
    }

    /**
     * Sets startDate.
     *
     * @param \DateTime $startDate
     *
     * @return $this
     */
    public function setStartDate(\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Gets startDate.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    /**
     * Sets status.
     *
     * @param TicketStatus $status
     *
     * @return $this
     */
    public function setStatus(TicketStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return TicketStatus
     */
    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    /**
     * Sets subcategory.
     *
     * @param TicketCategory $subcategory
     *
     * @return $this
     */
    public function setSubcategory(TicketCategory $subcategory)
    {
        $this->subcategory = $subcategory;

        return $this;
    }

    /**
     * Gets subcategory.
     *
     * @return TicketCategory
     */
    public function getSubcategory(): TicketCategory
    {
        return $this->subcategory;
    }

    /**
     * Adds supplementaryFile.
     *
     * @param DigitalDocument $supplementaryFile
     *
     * @return $this
     */
    public function addSupplementaryFile(DigitalDocument $supplementaryFile)
    {
        $this->supplementaryFiles[] = $supplementaryFile;

        return $this;
    }

    /**
     * Removes supplementaryFile.
     *
     * @param DigitalDocument $supplementaryFile
     *
     * @return $this
     */
    public function removeSupplementaryFile(DigitalDocument $supplementaryFile)
    {
        $this->supplementaryFiles->removeElement($supplementaryFile);

        return $this;
    }

    /**
     * Gets supplementaryFiles.
     *
     * @return DigitalDocument[]
     */
    public function getSupplementaryFiles(): array
    {
        return $this->supplementaryFiles->getValues();
    }

    /**
     * Sets ticketNumber.
     *
     * @param string $ticketNumber
     *
     * @return $this
     */
    public function setTicketNumber(string $ticketNumber)
    {
        $this->ticketNumber = $ticketNumber;

        return $this;
    }

    /**
     * Gets ticketNumber.
     *
     * @return string
     */
    public function getTicketNumber(): string
    {
        return $this->ticketNumber;
    }

    /**
     * Gets timeLeft.
     *
     * @return QuantitativeValue
     */
    public function getTimeLeft(): QuantitativeValue
    {
        return $this->timeLeft;
    }

    /**
     * Sets timeLeft.
     *
     * @param QuantitativeValue $timeLeft
     *
     * @return $this
     */
    public function setTimeLeft(QuantitativeValue $timeLeft)
    {
        $this->timeLeft = $timeLeft;

        return $this;
    }

    /**
     * Gets timer.
     *
     * @return QuantitativeValue
     */
    public function getTimer(): QuantitativeValue
    {
        return $this->timer;
    }

    /**
     * Sets timer.
     *
     * @param QuantitativeValue $timer
     *
     * @return $this
     */
    public function setTimer(QuantitativeValue $timer)
    {
        $this->timer = $timer;

        return $this;
    }

    /**
     * Sets type.
     *
     * @param TicketType $type
     *
     * @return $this
     */
    public function setType(TicketType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return TicketType
     */
    public function getType(): TicketType
    {
        return $this->type;
    }
}
