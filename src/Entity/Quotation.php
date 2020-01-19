<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\WonQuotationController;
use App\Enum\ContractType;
use App\Enum\QuotationStatus;
use App\Enum\VoltageType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A Quotation.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"quotation_read"}},
 *     "denormalization_context"={"groups"={"quotation_write"}},
 *     "filters"={
 *         "quotation.date",
 *         "quotation.order",
 *         "quotation.search",
 *     },
 *     "validation_groups"={Quotation::class, "validationGroups"},
 * },
 * itemOperations={
 *     "get",
 *     "put",
 *     "put_won_quotation"={
 *         "method"="POST",
 *         "path"="/won_quotation",
 *         "controller"=WonQuotationController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"quotation_read"}},
 *     },
 * })
 */
class Quotation
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
     * @var QuotationPriceConfiguration|null
     *
     * @ORM\OneToOne(targetEntity="QuotationPriceConfiguration", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $acceptedOffer;

    /**
     * @var Collection<Activity> The activity carried out on an item.
     *
     * @ORM\ManyToMany(targetEntity="Activity", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="quotation_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="activity_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $activities;

    /**
     * @var Collection<QuotationPostalAddress> The mailing address.
     *
     * @ORM\ManyToMany(targetEntity="QuotationPostalAddress", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="quotation_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="quotation_postal_address_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $addresses;

    /**
     * @var Collection<ApplicationRequest> An application request.
     *
     * @ORM\OneToMany(targetEntity="ApplicationRequest", cascade={"persist"}, mappedBy="quotation")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $applicationRequests;

    /**
     * @var User|null The user/employee assigned to the quotation.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $assignee;

    /**
     * @var QuantitativeValue The average consumption of electricity.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $averageConsumption;

    /**
     * @var \DateTime|null The date for the price given.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $brentCrudeDate;

    /**
     * @var PriceSpecification The price of brent crude oil.
     *
     * @ORM\Embedded(class="PriceSpecification")
     * @ApiProperty()
     */
    protected $brentCrudePrice;

    /**
     * @var CustomerAccount|null The contact person for the application request.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $contactPerson;

    /**
     * @var Collection<ContractDuration> The durations of contract offered by a quotation.
     *
     * @ORM\ManyToMany(targetEntity="ContractDuration", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="quotation_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="contract_duration_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $contractDurations;

    /**
     * @var string|null The contract subtype.
     *
     * @ORM\Column(type="contract_subtype_enum", nullable=true)
     * @ApiProperty()
     */
    protected $contractSubtype;

    /**
     * @var ContractType The pricePlan type for the quotation.
     *
     * @ORM\Column(type="contract_type_enum", nullable=false)
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
     * @var CustomerAccount|null Party placing the order or paying the invoice.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount", inversedBy="quotations")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/customer")
     */
    protected $customer;

    /**
     * @var bool|null Determines whether the deposit is negotiated.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $depositNegotiated;

    /**
     * @var \DateTime|null Date the content expires and is no longer useful or available.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/expires")
     */
    protected $expires;

    /**
     * @var DigitalDocument|null
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $file;

    /**
     * @var Collection<Note> The note added for the quotation.
     *
     * @ORM\ManyToMany(targetEntity="Note", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="quotation_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="note_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $notes;

    /**
     * @var Collection<QuotationPriceConfiguration> The price plan suggestions of the quotation.
     *
     * @ORM\ManyToMany(targetEntity="QuotationPriceConfiguration", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="quotation_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="price_configuration_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $offers;

    /**
     * @var string|null The payment mode of the quotation.
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     * @ApiProperty()
     */
    protected $paymentMode;

    /**
     * @var string|null The payment term of the quotation.
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     * @ApiProperty()
     */
    protected $paymentTerm;

    /**
     * @var Person|null A person (alive, dead, undead, or fictional).
     *
     * @ORM\OneToOne(targetEntity="Person", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $personDetails;

    /**
     * @var string The identifier of the quotation.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=false)
     * @ApiProperty()
     */
    protected $quotationNumber;

    /**
     * @var PriceSpecification The security deposit.
     *
     * @ORM\Embedded(class="PriceSpecification")
     * @ApiProperty()
     */
    protected $securityDeposit;

    /**
     * @var QuotationStatus The status of the quotation.
     *
     * @ORM\Column(type="quotation_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var array The contract terms of the quotation
     *
     * @ORM\Column(type="json", nullable=false)
     * @ApiProperty()
     */
    protected $terms;

    /**
     * @var UrlToken|null The url token.
     *
     * @ORM\OneToOne(targetEntity="UrlToken")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $urlToken;

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

    /**
     * @var VoltageType|null The voltage type.
     *
     * @ORM\Column(type="voltage_type_enum", nullable=true)
     * @ApiProperty()
     */
    protected $voltageType;

    /**
     * Quotation constructor.
     */
    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->applicationRequests = new ArrayCollection();
        $this->brentCrudePrice = new PriceSpecification();
        $this->contractDurations = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->offers = new ArrayCollection();
        $this->securityDeposit = new PriceSpecification();
        $this->terms = [];
    }

    /**
     * Return dynamic validation groups.
     *
     * @param self $quotation Contains the instance of Quotation to validate.
     *
     * @return string[]
     */
    public static function validationGroups(self $quotation)
    {
        $validationGroups = ['Default'];

        if (!\in_array($quotation->getStatus()->getValue(),
            [
                QuotationStatus::DRAFT,
            ], true
        )) {
            $validationGroups[] = 'finalized';
        }

        return $validationGroups;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return QuotationPriceConfiguration|null
     */
    public function getAcceptedOffer(): ?QuotationPriceConfiguration
    {
        return $this->acceptedOffer;
    }

    /**
     * @param QuotationPriceConfiguration|null $acceptedOffer
     */
    public function setAcceptedOffer(?QuotationPriceConfiguration $acceptedOffer): void
    {
        $this->acceptedOffer = $acceptedOffer;
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
     * @param QuotationPostalAddress $address
     *
     * @return $this
     */
    public function addAddress(QuotationPostalAddress $address)
    {
        $this->addresses[] = $address;

        return $this;
    }

    /**
     * Removes address.
     *
     * @param QuotationPostalAddress $address
     *
     * @return $this
     */
    public function removeAddress(QuotationPostalAddress $address)
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * Gets addresses.
     *
     * @return QuotationPostalAddress[]
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
     * @return User|null
     */
    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    /**
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
     * @return QuantitativeValue
     */
    public function getAverageConsumption(): QuantitativeValue
    {
        return $this->averageConsumption;
    }

    /**
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
     * @return \DateTime|null
     */
    public function getBrentCrudeDate(): ?\DateTime
    {
        return $this->brentCrudeDate;
    }

    /**
     * @param \DateTime|null $brentCrudeDate
     *
     * @return $this
     */
    public function setBrentCrudeDate(?\DateTime $brentCrudeDate)
    {
        $this->brentCrudeDate = $brentCrudeDate;

        return $this;
    }

    /**
     * @return PriceSpecification
     */
    public function getBrentCrudePrice(): PriceSpecification
    {
        return $this->brentCrudePrice;
    }

    /**
     * @param PriceSpecification $brentCrudePrice
     */
    public function setBrentCrudePrice(PriceSpecification $brentCrudePrice)
    {
        $this->brentCrudePrice = $brentCrudePrice;
    }

    /**
     * Sets contactPerson.
     *
     * @param CustomerAccount|null $contactPerson
     *
     * @return $this
     */
    public function setContactPerson(?CustomerAccount $contactPerson)
    {
        $this->contactPerson = $contactPerson;

        return $this;
    }

    /**
     * Gets contactPerson.
     *
     * @return CustomerAccount|null
     */
    public function getContactPerson(): ?CustomerAccount
    {
        return $this->contactPerson;
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
     * Adds contractDuration.
     *
     * @param ContractDuration $contractDuration
     *
     * @return $this
     */
    public function addContractDuration(ContractDuration $contractDuration)
    {
        $this->contractDurations[] = $contractDuration;

        return $this;
    }

    /**
     * Removes contractDuration.
     *
     * @param ContractDuration $contractDuration
     *
     * @return $this
     */
    public function removeContractDuration(ContractDuration $contractDuration)
    {
        $this->contractDurations->removeElement($contractDuration);

        return $this;
    }

    /**
     * Gets contractDurations.
     *
     * @return ContractDuration[]
     */
    public function getContractDurations(): array
    {
        return $this->contractDurations->getValues();
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
     * @return ContractType
     */
    public function getContractType(): ContractType
    {
        return $this->contractType;
    }

    /**
     * @param ContractType $contractType
     *
     * @return $this
     */
    public function setContractType(ContractType $contractType)
    {
        $this->contractType = $contractType;

        return $this;
    }

    /**
     * @return CustomerAccount|null
     */
    public function getCustomer(): ?CustomerAccount
    {
        return $this->customer;
    }

    /**
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
     * @return bool|null
     */
    public function isDepositNegotiated(): ?bool
    {
        return $this->depositNegotiated;
    }

    /**
     * @param bool|null $depositNegotiated
     */
    public function setDepositNegotiated(?bool $depositNegotiated)
    {
        $this->depositNegotiated = $depositNegotiated;
    }

    /**
     * @return \DateTime|null
     */
    public function getExpires(): ?\DateTime
    {
        return $this->expires;
    }

    /**
     * @return DigitalDocument|null
     */
    public function getFile(): ?DigitalDocument
    {
        return $this->file;
    }

    /**
     * @param DigitalDocument|null $file
     */
    public function setFile(?DigitalDocument $file): void
    {
        $this->file = $file;
    }

    /**
     * @param \DateTime|null $expires
     *
     * @return $this
     */
    public function setExpires(?\DateTime $expires)
    {
        $this->expires = $expires;

        return $this;
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
     * Adds offer.
     *
     * @param QuotationPriceConfiguration $offer
     *
     * @return $this
     */
    public function addOffer(QuotationPriceConfiguration $offer)
    {
        $this->offers[] = $offer;

        return $this;
    }

    /**
     * Removes offer.
     *
     * @param QuotationPriceConfiguration $offer
     *
     * @return $this
     */
    public function removeOffer(QuotationPriceConfiguration $offer)
    {
        $this->offers->removeElement($offer);

        return $this;
    }

    /**
     * Get offers.
     *
     * @return array
     */
    public function getOffers(): array
    {
        return $this->offers->getValues();
    }

    /**
     * @return string|null
     */
    public function getPaymentMode(): ?string
    {
        return $this->paymentMode;
    }

    /**
     * @param string|null $paymentMode
     *
     * @return $this
     */
    public function setPaymentMode(?string $paymentMode)
    {
        $this->paymentMode = $paymentMode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentTerm(): ?string
    {
        return $this->paymentTerm;
    }

    /**
     * @param string|null $paymentTerm
     *
     * @return $this
     */
    public function setPaymentTerm(?string $paymentTerm)
    {
        $this->paymentTerm = $paymentTerm;

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
     * @return string
     */
    public function getQuotationNumber(): string
    {
        return $this->quotationNumber;
    }

    /**
     * @param string $quotationNumber
     *
     * @return $this
     */
    public function setQuotationNumber(string $quotationNumber)
    {
        $this->quotationNumber = $quotationNumber;

        return $this;
    }

    /**
     * @return PriceSpecification
     */
    public function getSecurityDeposit(): PriceSpecification
    {
        return $this->securityDeposit;
    }

    /**
     * @param PriceSpecification $securityDeposit
     *
     * @return $this
     */
    public function setSecurityDeposit(PriceSpecification $securityDeposit)
    {
        $this->securityDeposit = $securityDeposit;

        return $this;
    }

    /**
     * @return QuotationStatus
     */
    public function getStatus(): QuotationStatus
    {
        return $this->status;
    }

    /**
     * @param QuotationStatus $status
     *
     * @return $this
     */
    public function setStatus(QuotationStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Adds term.
     *
     * @param array $term
     *
     * @return $this
     */
    public function addTerm(array $term)
    {
        $this->terms[] = $term;

        return $this;
    }

    /**
     * Removes term.
     *
     * @param array $term
     *
     * @return $this
     */
    public function removeTerm(array $term)
    {
        if (false !== ($key = \array_search($term, $this->terms, true))) {
            \array_splice($this->terms, $key, 1);
        }

        return $this;
    }

    /**
     * Gets terms.
     *
     * @return array
     */
    public function getTerms(): array
    {
        return $this->terms;
    }

    /**
     * Gets url token.
     *
     * @return UrlToken|null
     */
    public function getUrlToken(): ?UrlToken
    {
        return $this->urlToken;
    }

    /**
     * Sets url token.
     *
     * @param UrlToken|null $urlToken
     *
     * @return $this
     */
    public function setUrlToken(?UrlToken $urlToken)
    {
        $this->urlToken = $urlToken;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
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
     * @return \DateTime|null
     */
    public function getValidThrough(): ?\DateTime
    {
        return $this->validThrough;
    }

    /**
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
     * @return VoltageType|null
     */
    public function getVoltageType(): ?VoltageType
    {
        return $this->voltageType;
    }

    /**
     * @param VoltageType|null $voltageType
     *
     * @return $this
     */
    public function setVoltageType(?VoltageType $voltageType)
    {
        $this->voltageType = $voltageType;

        return $this;
    }
}
