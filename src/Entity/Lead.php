<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\AccountType;
use App\Enum\ContactMethod;
use App\Enum\ContractType;
use App\Enum\LeadScore;
use App\Enum\LeadStatus;
use App\Enum\MeterType;
use App\Enum\ReferralSource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * A business lead, can be used to create a quotation.
 *
 * @ORM\Entity(repositoryClass="App\Repository\LeadRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"lead_number"}),
 *     @ORM\Index(columns={"keywords"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"lead_read"}},
 *     "denormalization_context"={"groups"={"lead_write"}},
 *     "filters"={
 *         "lead.date",
 *         "lead.exists",
 *         "lead.json_search",
 *         "lead.order",
 *         "lead.range",
 *         "lead.search",
 *     },
 * })
 */
class Lead
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
     * @ORM\ManyToMany(targetEntity="Activity", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="lead_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="activity_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $activities;

    /**
     * @var Collection<PostalAddress> The mailing address.
     *
     * @ORM\ManyToMany(targetEntity="PostalAddress", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="lead_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="address_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $addresses;

    /**
     * @var Collection<ApplicationRequest> The application requests created from this lead.
     *
     * @ORM\OneToMany(targetEntity="ApplicationRequest", cascade={"persist"}, mappedBy="lead")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $applicationRequests;

    /**
     * @var User|null The user/employee assigned to the lead.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $assignee;

    /**
     * @var User|null The admin/employee who assigned the lead to another user/employee.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @Gedmo\Blameable(on="change", field={"assignee"})
     * @ApiProperty()
     */
    protected $assignor;

    /**
     * @var QuantitativeValue The average consumption of electricty.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $averageConsumption;

    /**
     * @var string|null For bridge use.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bridgeId;

    /**
     * @var string|null The contract subtype for the lead.
     *
     * @ORM\Column(type="contract_subtype_enum", nullable=true)
     * @ApiProperty()
     */
    protected $contractSubtype;

    /**
     * @var ContractType|null The contract type for the lead.
     *
     * @ORM\Column(type="contract_type_enum", nullable=true)
     * @ApiProperty()
     */
    protected $contractType;

    /**
     * @var Corporation|null Organization: A business corporation.
     *
     * @ORM\OneToOne(targetEntity="Corporation", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $corporationDetails;

    /**
     * @var string|null The previous seller/supplier of the lead.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty()
     */
    protected $customerOf;

    /**
     * @var \DateTime|null The date on which the follow up happened.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $dateFollowedUp;

    /**
     * @var bool|null Determines whether the lead should not be contacted.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $doNotContact;

    /**
     * @var bool|null Determines whether the lead is a customer of a separate product/department.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $existingCustomer;

    /**
     * @var string|null
     *
     * @ORM\Column(type="tsvector", nullable=true, options={
     *     "tsvector_fields"={
     *         "leadNumber"={
     *             "config"="english",
     *             "weight"="A",
     *         },
     *     },
     * })
     */
    protected $keywords;

    /**
     * @var string|null The identifier of the lead.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=true)
     * @ApiProperty()
     */
    protected $leadNumber;

    /**
     * @var bool|null Determines whether the lead is an LPG user.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $lpgUser;

    /**
     * @var MeterType|null The meter type of the lead.
     *
     * @ORM\Column(type="meter_type_enum", nullable=true)
     * @ApiProperty()
     */
    protected $meterType;

    /**
     * @var Collection<Note> The note added for the lead.
     *
     * @ORM\ManyToMany(targetEntity="Note", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="lead_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="note_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $notes;

    /**
     * @var Person|null A person (alive, dead, undead, or fictional).
     *
     * @ORM\OneToOne(targetEntity="Person", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $personDetails;

    /**
     * @var ContactMethod|null The preferred contact method of the person.
     *
     * @ORM\Column(type="contact_method_enum", nullable=true)
     * @ApiProperty()
     */
    protected $preferredContactMethod;

    /**
     * @var QuantitativeValue The time frame when the purchase will happen.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $purchaseTimeFrame;

    /**
     * @var ReferralSource|null The referral source.
     *
     * @ORM\Column(type="referral_source_enum", nullable=true)
     * @ApiProperty()
     */
    protected $referralSource;

    /**
     * @var LeadScore|null The score of the lead.
     *
     * @ORM\Column(type="lead_score_enum", nullable=true)
     * @ApiProperty()
     */
    protected $score;

    /**
     * @var string|null A referral source specified if not included in referral source list.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty()
     */
    protected $specifiedReferralSource;

    /**
     * @var LeadStatus The status of the lead.
     *
     * @ORM\Column(type="lead_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var TariffRate|null The tariff rate.
     *
     * @ORM\ManyToOne(targetEntity="TariffRate")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $tariffRate;

    /**
     * @var bool|null Determines whether the lead is a tenant.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $tenant;

    /**
     * @var AccountType The lead type.
     *
     * @ORM\Column(type="account_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->applicationRequests = new ArrayCollection();
        $this->averageConsumption = new QuantitativeValue();
        $this->purchaseTimeFrame = new QuantitativeValue();
        $this->notes = new ArrayCollection();
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
     * Adds activity.
     *
     * @param Activity $activity
     *
     * @return $this
     */
    public function addActivity(Activity $activity)
    {
        $this->activities[] = $activity;

        return $this;
    }

    /**
     * Removes activity.
     *
     * @param Activity $activity
     *
     * @return $this
     */
    public function removeActivity(Activity $activity)
    {
        $this->activities->removeElement($activity);

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
     * Adds address.
     *
     * @param PostalAddress $address
     *
     * @return $this
     */
    public function addAddress(PostalAddress $address)
    {
        $this->addresses[] = $address;

        return $this;
    }

    /**
     * Removes address.
     *
     * @param PostalAddress $address
     *
     * @return $this
     */
    public function removeAddress(PostalAddress $address)
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * Gets addresses.
     *
     * @return PostalAddress[]
     */
    public function getAddresses(): array
    {
        return $this->addresses->getValues();
    }

    /**
     * Adds applicationRequest.
     *
     * @param ApplicationRequest $applicationRequest
     *
     * @return $this
     */
    public function addApplicationRequest(ApplicationRequest $applicationRequest)
    {
        $this->applicationRequests[] = $applicationRequest;

        return $this;
    }

    /**
     * Removes applicationRequest.
     *
     * @param ApplicationRequest $applicationRequest
     *
     * @return $this
     */
    public function removeApplicationRequest(ApplicationRequest $applicationRequest)
    {
        $this->applicationRequests->removeElement($applicationRequest);

        return $this;
    }

    /**
     * Gets applicationRequests.
     *
     * @return ApplicationRequest[]
     */
    public function getApplicationRequests(): array
    {
        return $this->applicationRequests->getValues();
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
     * Sets assignor.
     *
     * @param User|null $assignor
     *
     * @return $this
     */
    public function setAssignor(?User $assignor)
    {
        $this->assignor = $assignor;

        return $this;
    }

    /**
     * Gets assignor.
     *
     * @return User|null
     */
    public function getAssignor(): ?User
    {
        return $this->assignor;
    }

    /**
     * Sets averageConsumption.
     *
     * @param QuantitativeValue $averageConsumption
     *
     * @return $this
     */
    public function setAverageConsumption(QuantitativeValue $averageConsumption)
    {
        $this->averageConsumption = $averageConsumption;

        return $this;
    }

    /**
     * Gets averageConsumption.
     *
     * @return QuantitativeValue
     */
    public function getAverageConsumption(): QuantitativeValue
    {
        return $this->averageConsumption;
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
     * Sets contractSubtype.
     *
     * @param string|null $contractSubtype
     *
     * @return $this
     */
    public function setContractSubtype(?string $contractSubtype)
    {
        $this->contractSubtype = $contractSubtype;

        return $this;
    }

    /**
     * Gets contractSubtype.
     *
     * @return string|null
     */
    public function getContractSubtype(): ?string
    {
        return $this->contractSubtype;
    }

    /**
     * Sets contractType.
     *
     * @param ContractType|null $contractType
     *
     * @return $this
     */
    public function setContractType(?ContractType $contractType)
    {
        $this->contractType = $contractType;

        return $this;
    }

    /**
     * Gets contractType.
     *
     * @return ContractType|null
     */
    public function getContractType(): ?ContractType
    {
        return $this->contractType;
    }

    /**
     * Sets corporationDetails.
     *
     * @param Corporation|null $corporationDetails
     *
     * @return $this
     */
    public function setCorporationDetails(?Corporation $corporationDetails)
    {
        $this->corporationDetails = $corporationDetails;

        return $this;
    }

    /**
     * Gets corporationDetails.
     *
     * @return Corporation|null
     */
    public function getCorporationDetails(): ?Corporation
    {
        return $this->corporationDetails;
    }

    /**
     * Sets customerOf.
     *
     * @param string|null $customerOf
     *
     * @return $this
     */
    public function setCustomerOf(?string $customerOf)
    {
        $this->customerOf = $customerOf;

        return $this;
    }

    /**
     * Gets customerOf.
     *
     * @return string|null
     */
    public function getCustomerOf(): ?string
    {
        return $this->customerOf;
    }

    /**
     * Sets dateFollowedUp.
     *
     * @param \DateTime|null $dateFollowedUp
     *
     * @return $this
     */
    public function setDateFollowedUp(?\DateTime $dateFollowedUp)
    {
        $this->dateFollowedUp = $dateFollowedUp;

        return $this;
    }

    /**
     * Gets dateFollowedUp.
     *
     * @return \DateTime|null
     */
    public function getDateFollowedUp(): ?\DateTime
    {
        return $this->dateFollowedUp;
    }

    /**
     * Sets doNotContact.
     *
     * @param bool|null $doNotContact
     *
     * @return $this
     */
    public function setDoNotContact(?bool $doNotContact)
    {
        $this->doNotContact = $doNotContact;

        return $this;
    }

    /**
     * Gets doNotContact.
     *
     * @return bool|null
     */
    public function isDoNotContact(): ?bool
    {
        return $this->doNotContact;
    }

    /**
     * Sets existingCustomer.
     *
     * @param bool|null $existingCustomer
     *
     * @return $this
     */
    public function setExistingCustomer(?bool $existingCustomer)
    {
        $this->existingCustomer = $existingCustomer;

        return $this;
    }

    /**
     * Gets existingCustomer.
     *
     * @return bool|null
     */
    public function isExistingCustomer(): ?bool
    {
        return $this->existingCustomer;
    }

    /**
     * Sets leadNumber.
     *
     * @param string|null $leadNumber
     *
     * @return $this
     */
    public function setLeadNumber(?string $leadNumber)
    {
        $this->leadNumber = $leadNumber;

        return $this;
    }

    /**
     * Gets leadNumber.
     *
     * @return string|null
     */
    public function getLeadNumber(): ?string
    {
        return $this->leadNumber;
    }

    /**
     * Sets lpgUser.
     *
     * @param bool|null $lpgUser
     *
     * @return $this
     */
    public function setLpgUser(?bool $lpgUser)
    {
        $this->lpgUser = $lpgUser;

        return $this;
    }

    /**
     * Gets lpgUser.
     *
     * @return bool|null
     */
    public function isLpgUser(): ?bool
    {
        return $this->lpgUser;
    }

    /**
     * Sets meterType.
     *
     * @param MeterType|null $meterType
     *
     * @return $this
     */
    public function setMeterType(?MeterType $meterType)
    {
        $this->meterType = $meterType;

        return $this;
    }

    /**
     * Gets meterType.
     *
     * @return MeterType|null
     */
    public function getMeterType(): ?MeterType
    {
        return $this->meterType;
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
     * Sets preferredContactMethod.
     *
     * @param ContactMethod|null $preferredContactMethod
     *
     * @return $this
     */
    public function setPreferredContactMethod(?ContactMethod $preferredContactMethod)
    {
        $this->preferredContactMethod = $preferredContactMethod;

        return $this;
    }

    /**
     * Gets preferredContactMethod.
     *
     * @return ContactMethod|null
     */
    public function getPreferredContactMethod(): ?ContactMethod
    {
        return $this->preferredContactMethod;
    }

    /**
     * Sets purchaseTimeFrame.
     *
     * @param QuantitativeValue $purchaseTimeFrame
     *
     * @return $this
     */
    public function setPurchaseTimeFrame(QuantitativeValue $purchaseTimeFrame)
    {
        $this->purchaseTimeFrame = $purchaseTimeFrame;

        return $this;
    }

    /**
     * Gets purchaseTimeFrame.
     *
     * @return QuantitativeValue
     */
    public function getPurchaseTimeFrame(): QuantitativeValue
    {
        return $this->purchaseTimeFrame;
    }

    /**
     * @return ReferralSource|null
     */
    public function getReferralSource(): ?ReferralSource
    {
        return $this->referralSource;
    }

    /**
     * @param ReferralSource|null $referralSource
     */
    public function setReferralSource(?ReferralSource $referralSource): void
    {
        $this->referralSource = $referralSource;
    }

    /**
     * Sets score.
     *
     * @param LeadScore|null $score
     *
     * @return $this
     */
    public function setScore(?LeadScore $score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Gets score.
     *
     * @return LeadScore|null
     */
    public function getScore(): ?LeadScore
    {
        return $this->score;
    }

    /**
     * @return string|null
     */
    public function getSpecifiedReferralSource(): ?string
    {
        return $this->specifiedReferralSource;
    }

    /**
     * @param string|null $specifiedReferralSource
     */
    public function setSpecifiedReferralSource(?string $specifiedReferralSource): void
    {
        $this->specifiedReferralSource = $specifiedReferralSource;
    }

    /**
     * Sets status.
     *
     * @param LeadStatus $status
     *
     * @return $this
     */
    public function setStatus(LeadStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return LeadStatus
     */
    public function getStatus(): LeadStatus
    {
        return $this->status;
    }

    /**
     * Sets tariffRate.
     *
     * @param TariffRate|null $tariffRate
     *
     * @return $this
     */
    public function setTariffRate(?TariffRate $tariffRate)
    {
        $this->tariffRate = $tariffRate;

        return $this;
    }

    /**
     * Gets tariffRate.
     *
     * @return TariffRate|null
     */
    public function getTariffRate(): ?TariffRate
    {
        return $this->tariffRate;
    }

    /**
     * Sets tenant.
     *
     * @param bool|null $tenant
     *
     * @return $this
     */
    public function setTenant(?bool $tenant)
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * Gets tenant.
     *
     * @return bool|null
     */
    public function isTenant(): ?bool
    {
        return $this->tenant;
    }

    /**
     * Sets type.
     *
     * @param AccountType $type
     *
     * @return $this
     */
    public function setType(AccountType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return AccountType
     */
    public function getType(): AccountType
    {
        return $this->type;
    }
}
