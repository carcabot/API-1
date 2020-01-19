<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Controller\SignOnBehalfAuthorizationController;
use App\Enum\AccountType;
use App\Enum\ApplicationRequestStatus;
use App\Enum\ApplicationRequestType;
use App\Enum\ContractType;
use App\Enum\MeterType;
use App\Enum\PaymentMode;
use App\Enum\ReferralSource;
use App\Enum\RefundType;
use App\WebService\Billing\Controller\ApplicationRequestStatusHistoryController;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An application request.
 *
 * @ORM\Entity(repositoryClass="App\Repository\ApplicationRequestRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"application_request_number"}),
 *     @ORM\Index(columns={"keywords"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"application_request_read"}},
 *     "denormalization_context"={"groups"={"application_request_write"}},
 *     "filters"={
 *         "application_request.boolean",
 *         "application_request.date",
 *         "application_request.json_search",
 *         "application_request.order",
 *         "application_request.search",
 *     },
 *     "validation_groups"={ApplicationRequest::class, "validationGroups"},
 * },
 * itemOperations={
 *     "delete",
 *     "get",
 *     "get_status_history"={
 *         "method"="GET",
 *         "path"="/application_requests/{id}/status_history.{_format}",
 *         "controller"=ApplicationRequestStatusHistoryController::class
 *     },
 *     "put",
 *     "put_sign_on_behalf_application_request"={
 *         "method"="POST",
 *         "path"="/sign_on_behalf_application_request",
 *         "controller"=SignOnBehalfAuthorizationController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"application_request_sign_on_behalf_read"}},
 *     },
 * })
 */
class ApplicationRequest
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
     * @var string|null The code of the acquiredFrom in the SourceableTrait.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty()
     */
    protected $acquirerCode;

    /**
     * @var string|null The name of the acquiredFrom in the SourceableTrait.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty()
     */
    protected $acquirerName;

    /**
     * @var Collection<Activity> The activity carried out on an item.
     *
     * @ORM\ManyToMany(targetEntity="Activity", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="application_request_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="activity_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $activities;

    /**
     * @var Collection<AddonService> An add-on service.
     *
     * @ORM\ManyToMany(targetEntity="AddonService", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="application_request_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="addon_service_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $addonServices;

    /**
     * @var Collection<PostalAddress> The mailing address.
     *
     * @ORM\ManyToMany(targetEntity="PostalAddress", cascade={"persist", "refresh"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="application_request_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="address_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $addresses;

    /**
     * @var AdvisoryNotice|null A version of Advisory Notice.
     *
     * @ORM\ManyToOne(targetEntity="AdvisoryNotice")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $advisoryNotice;

    /**
     * @var string The identifier of the application request.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=false)
     * @ApiProperty()
     */
    protected $applicationRequestNumber;

    /**
     * @var QuantitativeValue The average consumption of electricty.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $averageConsumption;

    /**
     * @var string[] The bill subscription type.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $billSubscriptionTypes;

    /**
     * @var string|null For bridge use.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bridgeId;

    /**
     * @var CustomerAccount|null The contact person for the application request.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $contactPerson;

    /**
     * @var Contract|null A contract.
     *
     * @ORM\ManyToOne(targetEntity="Contract", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $contract;

    /**
     * @var string|null The contract subtype.
     *
     * @ORM\Column(type="contract_subtype_enum", nullable=true)
     * @ApiProperty()
     */
    protected $contractSubtype;

    /**
     * @var ContractType|null The contract type.
     *
     * @ORM\Column(type="contract_type_enum", nullable=true)
     * @ApiProperty()
     */
    protected $contractType;

    /**
     * @var Corporation|null Organization: A business corporation.
     *
     * @ORM\OneToOne(targetEntity="Corporation", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $corporationDetails;

    /**
     * @var CustomerAccount|null Party placing the order or paying the invoice.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount", inversedBy="applicationRequests", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/customer")
     */
    protected $customer;

    /**
     * @var string|null The previous seller/supplier of the lead.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty()
     */
    protected $customerOf;

    /**
     * @var CustomerAccount|null
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $customerRepresentative;

    /**
     * @var AccountType|null The customer account type.
     *
     * @ORM\Column(type="account_type_enum", nullable=true)
     * @ApiProperty()
     */
    protected $customerType;

    /**
     * @var bool|null Determines whether the application request has been customized.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $customized;

    /**
     * @var \DateTime|null The date of application request submission.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $dateSubmitted;

    /**
     * @var MonetaryAmount The deposit amount.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $depositAmount;

    /**
     * @var RefundType|null The refund type for the application request.
     *
     * @ORM\Column(type="refund_type_enum", nullable=true)
     * @ApiProperty()
     */
    protected $depositRefundType;

    /**
     * @var string|null The EBS account number.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty()
     */
    protected $ebsAccountNumber;

    /**
     * @var string|null The external application request number.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $externalApplicationRequestNumber;

    /**
     * @var bool|null Determines whether GIRO option is enabled.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $giroOption;

    /**
     * @var string|null
     *
     * @ORM\Column(type="tsvector", nullable=true, options={
     *     "tsvector_fields"={
     *         "applicationRequestNumber"={
     *             "config"="english",
     *             "weight"="A",
     *         },
     *     },
     * })
     */
    protected $keywords;

    /**
     * @var Lead|null The lead this application request was created from.
     *
     * @ORM\ManyToOne(targetEntity="Lead", inversedBy="applicationRequests")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $lead;

    /**
     * @var string|null The location of for example where the event is happening, an organization is located, or where an action takes place.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty(iri="http://schema.org/location")
     */
    protected $location;

    /**
     * @var MeterType|null The meter type of the application request.
     *
     * @ORM\Column(type="meter_type_enum", nullable=true)
     * @ApiProperty()
     */
    protected $meterType;

    /**
     * @var string|null The MSSL account number.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty()
     */
    protected $msslAccountNumber;

    /**
     * @var PaymentMode|null The payment mode.
     *
     * @ORM\Column(type="payment_mode_enum", nullable=true)
     * @ApiProperty()
     */
    protected $paymentMode;

    /**
     * @var Person|null A person (alive, dead, undead, or fictional).
     *
     * @ORM\OneToOne(targetEntity="Person", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $personDetails;

    /**
     * @var \DateTime|null The preferred end date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $preferredEndDate;

    /**
     * @var \DateTime|null The preferred start date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $preferredStartDate;

    /**
     * @var Promotion|null The promotion code applied
     *
     * @ORM\ManyToOne(targetEntity="Promotion", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $promotion;

    /**
     * @var Quotation|null The quotation for the application request.
     *
     * @ORM\ManyToOne(targetEntity="Quotation", inversedBy="applicationRequests")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $quotation;

    /**
     * @var QuotationPriceConfiguration|null The quotation offer for the application request.
     *
     * @ORM\ManyToOne(targetEntity="QuotationPriceConfiguration")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $quotationOffer;

    /**
     * @var bool|null Determines whether the recurring option is enabled.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $recurringOption;

    /**
     * @var string|null The referral code.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty()
     */
    protected $referralCode;

    /**
     * @var ReferralSource|null The referral source.
     *
     * @ORM\Column(type="referral_source_enum", nullable=true)
     * @ApiProperty()
     */
    protected $referralSource;

    /**
     * @var CustomerAccount|null One who receives a refund.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $refundee;

    /**
     * @var Person|null A person (alive, dead, undead, or fictional).
     *
     * @ORM\OneToOne(targetEntity="Person", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $refundeeDetails;

    /**
     * @var string|null A remark.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $remark;

    /**
     * @var Person|null A person (alive, dead, undead, or fictional).
     *
     * @ORM\OneToOne(targetEntity="Person", cascade={"persist", "refresh"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $representativeDetails;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty()
     */
    protected $salesRepName;

    /**
     * @var bool|null Determines whether the application request is applied by the customer.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $selfApplication;

    /**
     * @var bool|null Determines whether self read meter option is enabled.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $selfReadMeterOption;

    /**
     * @var string|null A referral source specified if not included in referral source list.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty()
     */
    protected $specifiedReferralSource;

    /**
     * @var ApplicationRequestStatus The status of the application request.
     *
     * @ORM\Column(type="application_request_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var User|null The user who submits the application request.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $submitter;

    /**
     * @var Collection<DigitalDocument> A file attached to the application request.
     *
     * @ORM\ManyToMany(targetEntity="DigitalDocument", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="application_request_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", unique=true, onDelete="CASCADE")},
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $supplementaryFiles;

    /**
     * @var string|null The temporary identifier of the application request.
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     * @ApiProperty()
     */
    protected $temporaryNumber;

    /**
     * @var \DateTime|null Date to terminate contract.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $terminationDate;

    /**
     * @var TariffRate|null The tariff rate applied for.
     *
     * @ORM\ManyToOne(targetEntity="TariffRate", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $tariffRate;

    /**
     * @var string|null Reason for terminating contract.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty()
     */
    protected $terminationReason;

    /**
     * @var ApplicationRequestType The application request type.
     *
     * @ORM\Column(type="application_request_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var UrlToken|null The url token.
     *
     * @ORM\OneToOne(targetEntity="UrlToken")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $urlToken;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->addonServices = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->averageConsumption = new QuantitativeValue();
        $this->billSubscriptionTypes = [];
        $this->depositAmount = new MonetaryAmount();
        $this->supplementaryFiles = new ArrayCollection();
    }

    /**
     * Return dynamic validation groups.
     *
     * @param self $applicationRequest Contains the instance of ApplicationRequest to validate.
     *
     * @return string[]
     */
    public static function validationGroups(self $applicationRequest)
    {
        $validationGroups = ['Default'];

        if (!\in_array($applicationRequest->getStatus()->getValue(),
            [
                ApplicationRequestStatus::DRAFT,
                ApplicationRequestStatus::PARTNER_DRAFT,
                ApplicationRequestStatus::VOIDED,
            ], true
        )) {
            $validationGroups[] = 'finalized';

            if (ApplicationRequestType::CONTRACT_APPLICATION === $applicationRequest->getType()->getValue()) {
                $validationGroups[] = 'finalized_contract_application';
            }
        }

        return $validationGroups;
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
     * Sets acquirerCode.
     *
     * @param string|null $acquirerCode
     *
     * @return $this
     */
    public function setAcquirerCode(?string $acquirerCode)
    {
        $this->acquirerCode = $acquirerCode;

        return $this;
    }

    /**
     * Gets acquirerCode.
     *
     * @return string|null
     */
    public function getAcquirerCode(): ?string
    {
        return $this->acquirerCode;
    }

    /**
     * Sets acquirerName.
     *
     * @param string|null $acquirerName
     *
     * @return $this
     */
    public function setAcquirerName(?string $acquirerName)
    {
        $this->acquirerName = $acquirerName;

        return $this;
    }

    /**
     * Gets acquirerName.
     *
     * @return string|null
     */
    public function getAcquirerName(): ?string
    {
        return $this->acquirerName;
    }

    /**
     * Adds addonService.
     *
     * @param AddonService $addonService
     *
     * @return $this
     */
    public function addAddonService(AddonService $addonService)
    {
        $this->addonServices[] = $addonService;

        return $this;
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
     * Removes addonService.
     *
     * @param AddonService $addonService
     *
     * @return $this
     */
    public function removeAddonService(AddonService $addonService)
    {
        $this->addonServices->removeElement($addonService);

        return $this;
    }

    /**
     * Clears addonServices.
     *
     * @return $this
     */
    public function clearAddonServices()
    {
        $this->addonServices = new ArrayCollection();

        return $this;
    }

    /**
     * Get addonServices.
     *
     * @return AddonService[]
     */
    public function getAddonServices(): array
    {
        return $this->addonServices->getValues();
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
     * Get addresses.
     *
     * @return PostalAddress[]
     */
    public function getAddresses(): array
    {
        return $this->addresses->getValues();
    }

    /**
     * Sets advisoryNotice.
     *
     * @param AdvisoryNotice|null $advisoryNotice
     *
     * @return $this
     */
    public function setAdvisoryNotice(?AdvisoryNotice $advisoryNotice)
    {
        $this->advisoryNotice = $advisoryNotice;

        return $this;
    }

    /**
     * Gets advisoryNotice.
     *
     * @return AdvisoryNotice|null
     */
    public function getAdvisoryNotice(): ?AdvisoryNotice
    {
        return $this->advisoryNotice;
    }

    /**
     * Sets applicationRequestNumber.
     *
     * @param string $applicationRequestNumber
     *
     * @return $this
     */
    public function setApplicationRequestNumber(string $applicationRequestNumber)
    {
        $this->applicationRequestNumber = $applicationRequestNumber;

        return $this;
    }

    /**
     * Gets applicationRequestNumber.
     *
     * @return string
     */
    public function getApplicationRequestNumber(): string
    {
        return $this->applicationRequestNumber;
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
     * Adds billSubscriptionType.
     *
     * @param string $billSubscriptionType
     *
     * @return $this
     */
    public function addBillSubscriptionType(string $billSubscriptionType)
    {
        $this->billSubscriptionTypes[] = $billSubscriptionType;

        return $this;
    }

    /**
     * Removes billSubscriptionType.
     *
     * @param string $billSubscriptionType
     *
     * @return $this
     */
    public function removeBillSubscriptionType(string $billSubscriptionType)
    {
        if (false !== ($key = \array_search($billSubscriptionType, $this->billSubscriptionTypes, true))) {
            \array_splice($this->billSubscriptionTypes, $key, 1);
        }

        return $this;
    }

    /**
     * Gets billSubscriptionTypes.
     *
     * @return string[]
     */
    public function getBillSubscriptionTypes(): array
    {
        return $this->billSubscriptionTypes;
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
     * Gets customer representative.
     *
     * @return CustomerAccount|null
     */
    public function getCustomerRepresentative(): ?CustomerAccount
    {
        return $this->customerRepresentative;
    }

    /**
     * Sets customer representative.
     *
     * @param CustomerAccount|null $customerRepresentative
     *
     * @return $this
     */
    public function setCustomerRepresentative(?CustomerAccount $customerRepresentative)
    {
        $this->customerRepresentative = $customerRepresentative;

        return $this;
    }

    /**
     * Sets customerType.
     *
     * @param AccountType|null $customerType
     *
     * @return $this
     */
    public function setCustomerType(?AccountType $customerType)
    {
        $this->customerType = $customerType;

        return $this;
    }

    /**
     * Gets customerType.
     *
     * @return AccountType|null
     */
    public function getCustomerType(): ?AccountType
    {
        return $this->customerType;
    }

    /**
     * Sets customized.
     *
     * @param bool|null $customized
     *
     * @return $this
     */
    public function setCustomized(?bool $customized)
    {
        $this->customized = $customized;

        return $this;
    }

    /**
     * Gets customized.
     *
     * @return bool|null
     */
    public function isCustomized(): ?bool
    {
        return $this->customized;
    }

    /**
     * Sets dateSubmitted.
     *
     * @param \DateTime|null $dateSubmitted
     *
     * @return $this
     */
    public function setDateSubmitted(?\DateTime $dateSubmitted)
    {
        $this->dateSubmitted = $dateSubmitted;

        return $this;
    }

    /**
     * Gets dateSubmitted.
     *
     * @return \DateTime|null
     */
    public function getDateSubmitted(): ?\DateTime
    {
        return $this->dateSubmitted;
    }

    /**
     * Sets depositAmount.
     *
     * @param MonetaryAmount $depositAmount
     *
     * @return $this
     */
    public function setDepositAmount(MonetaryAmount $depositAmount)
    {
        $this->depositAmount = $depositAmount;

        return $this;
    }

    /**
     * Gets depositAmount.
     *
     * @return MonetaryAmount
     */
    public function getDepositAmount(): MonetaryAmount
    {
        return $this->depositAmount;
    }

    /**
     * Sets depositRefundType.
     *
     * @param RefundType|null $depositRefundType
     *
     * @return $this
     */
    public function setDepositRefundType(?RefundType $depositRefundType)
    {
        $this->depositRefundType = $depositRefundType;

        return $this;
    }

    /**
     * Gets depositRefundType.
     *
     * @return RefundType|null
     */
    public function getDepositRefundType(): ?RefundType
    {
        return $this->depositRefundType;
    }

    /**
     * Sets ebsAccountNumber.
     *
     * @param string|null $ebsAccountNumber
     *
     * @return $this
     */
    public function setEbsAccountNumber(?string $ebsAccountNumber)
    {
        $this->ebsAccountNumber = $ebsAccountNumber;

        return $this;
    }

    /**
     * Gets ebsAccountNumber.
     *
     * @return string|null
     */
    public function getEbsAccountNumber(): ?string
    {
        return $this->ebsAccountNumber;
    }

    /**
     * Sets externalApplicationRequestNumber.
     *
     * @param string|null $externalApplicationRequestNumber
     *
     * @return $this
     */
    public function setExternalApplicationRequestNumber(?string $externalApplicationRequestNumber)
    {
        $this->externalApplicationRequestNumber = $externalApplicationRequestNumber;

        return $this;
    }

    /**
     * Gets externalApplicationRequestNumber.
     *
     * @return string|null
     */
    public function getExternalApplicationRequestNumber(): ?string
    {
        return $this->externalApplicationRequestNumber;
    }

    /**
     * Sets giroOption.
     *
     * @param bool|null $giroOption
     *
     * @return $this
     */
    public function setGIROOption(?bool $giroOption)
    {
        $this->giroOption = $giroOption;

        return $this;
    }

    /**
     * Gets giroOption.
     *
     * @return bool|null
     */
    public function isGIROOption(): ?bool
    {
        return $this->giroOption;
    }

    /**
     * Sets lead.
     *
     * @param Lead|null $lead
     *
     * @return $this
     */
    public function setLead(?Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Gets lead.
     *
     * @return Lead
     */
    public function getLead(): ?Lead
    {
        return $this->lead;
    }

    /**
     * Sets location.
     *
     * @param string|null $location
     *
     * @return $this
     */
    public function setLocation(?string $location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Gets location.
     *
     * @return string|null
     */
    public function getLocation(): ?string
    {
        return $this->location;
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
     * Sets msslAccountNumber.
     *
     * @param string|null $msslAccountNumber
     *
     * @return $this
     */
    public function setMsslAccountNumber(?string $msslAccountNumber)
    {
        $this->msslAccountNumber = $msslAccountNumber;

        return $this;
    }

    /**
     * Gets msslAccountNumber.
     *
     * @return string|null
     */
    public function getMsslAccountNumber(): ?string
    {
        return $this->msslAccountNumber;
    }

    /**
     * Gets payment mode.
     *
     * @return PaymentMode|null
     */
    public function getPaymentMode(): ?PaymentMode
    {
        return $this->paymentMode;
    }

    /**
     * Sets paymentMode.
     *
     * @param PaymentMode|null $paymentMode
     *
     * @return $this
     */
    public function setPaymentMode(?PaymentMode $paymentMode)
    {
        $this->paymentMode = $paymentMode;

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
     * Sets preferredEndDate.
     *
     * @param \DateTime|null $preferredEndDate
     *
     * @return $this
     */
    public function setPreferredEndDate(?\DateTime $preferredEndDate)
    {
        $this->preferredEndDate = $preferredEndDate;

        return $this;
    }

    /**
     * Gets preferredEndDate.
     *
     * @return \DateTime|null
     */
    public function getPreferredEndDate(): ?\DateTime
    {
        return $this->preferredEndDate;
    }

    /**
     * Sets preferredStartDate.
     *
     * @param \DateTime|null $preferredStartDate
     *
     * @return $this
     */
    public function setPreferredStartDate(?\DateTime $preferredStartDate)
    {
        $this->preferredStartDate = $preferredStartDate;

        return $this;
    }

    /**
     * Gets preferredStartDate.
     *
     * @return \DateTime|null
     */
    public function getPreferredStartDate(): ?\DateTime
    {
        return $this->preferredStartDate;
    }

    /**
     * @return Promotion|null
     */
    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }

    /**
     * @param Promotion|null $promotion
     */
    public function setPromotion(?Promotion $promotion): void
    {
        $this->promotion = $promotion;
    }

    /**
     * @return Quotation|null
     */
    public function getQuotation(): ?Quotation
    {
        return $this->quotation;
    }

    /**
     * @param Quotation|null $quotation
     *
     * @return $this
     */
    public function setQuotation(?Quotation $quotation)
    {
        $this->quotation = $quotation;

        return $this;
    }

    /**
     * Sets quotationOffer.
     *
     * @param QuotationPriceConfiguration|null $quotationOffer
     *
     * @return $this
     */
    public function setQuotationOffer(?QuotationPriceConfiguration $quotationOffer)
    {
        $this->quotationOffer = $quotationOffer;

        return $this;
    }

    /**
     * Gets quotationOffer.
     *
     * @return QuotationPriceConfiguration|null
     */
    public function getQuotationOffer(): ?QuotationPriceConfiguration
    {
        return $this->quotationOffer;
    }

    /**
     * Sets recurringOption.
     *
     * @param bool|null $recurringOption
     *
     * @return $this
     */
    public function setRecurringOption(?bool $recurringOption)
    {
        $this->recurringOption = $recurringOption;

        return $this;
    }

    /**
     * Gets recurringOption.
     *
     * @return bool|null
     */
    public function isRecurringOption(): ?bool
    {
        return $this->recurringOption;
    }

    /**
     * Gets referral code.
     *
     * @return string|null
     */
    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }

    /**
     * Sets referral code.
     *
     * @param string|null $referralCode
     *
     * @return $this
     */
    public function setReferralCode(?string $referralCode)
    {
        $this->referralCode = $referralCode;

        return $this;
    }

    /**
     * Sets referralSource.
     *
     * @param ReferralSource|null $referralSource
     *
     * @return $this
     */
    public function setReferralSource(?ReferralSource $referralSource)
    {
        $this->referralSource = $referralSource;

        return $this;
    }

    /**
     * Gets referralSource.
     *
     * @return ReferralSource|null
     */
    public function getReferralSource(): ?ReferralSource
    {
        return $this->referralSource;
    }

    /**
     * Sets refundee.
     *
     * @param CustomerAccount|null $refundee
     *
     * @return $this
     */
    public function setRefundee(?CustomerAccount $refundee)
    {
        $this->refundee = $refundee;

        return $this;
    }

    /**
     * Gets refundee.
     *
     * @return CustomerAccount|null
     */
    public function getRefundee(): ?CustomerAccount
    {
        return $this->refundee;
    }

    /**
     * Sets refundeeDetails.
     *
     * @param Person|null $refundeeDetails
     *
     * @return $this
     */
    public function setRefundeeDetails(?Person $refundeeDetails)
    {
        $this->refundeeDetails = $refundeeDetails;

        return $this;
    }

    /**
     * Gets refundeeDetails.
     *
     * @return Person|null
     */
    public function getRefundeeDetails(): ?Person
    {
        return $this->refundeeDetails;
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
     * Gets Representative details.
     *
     * @return Person|null
     */
    public function getRepresentativeDetails(): ?Person
    {
        return $this->representativeDetails;
    }

    /**
     * Sets representative details.
     *
     * @param Person|null $representativeDetails
     *
     * @return $this
     */
    public function setRepresentativeDetails(?Person $representativeDetails)
    {
        $this->representativeDetails = $representativeDetails;

        return $this;
    }

    /**
     * Gets sales rep name.
     *
     * @return string|null
     */
    public function getSalesRepName(): ?string
    {
        return $this->salesRepName;
    }

    /**
     * Sets sales rep name.
     *
     * @param string|null $salesRepName
     */
    public function setSalesRepName(?string $salesRepName): void
    {
        $this->salesRepName = $salesRepName;
    }

    /**
     * Sets selfApplication.
     *
     * @param bool|null $selfApplication
     *
     * @return $this
     */
    public function setSelfApplication(?bool $selfApplication)
    {
        $this->selfApplication = $selfApplication;

        return $this;
    }

    /**
     * Gets selfApplication.
     *
     * @return bool|null
     */
    public function isSelfApplication(): ?bool
    {
        return $this->selfApplication;
    }

    /**
     * Sets selfReadMeterOption.
     *
     * @param bool|null $selfReadMeterOption
     *
     * @return $this
     */
    public function setSelfReadMeterOption(?bool $selfReadMeterOption)
    {
        $this->selfReadMeterOption = $selfReadMeterOption;

        return $this;
    }

    /**
     * Gets selfReadMeterOption.
     *
     * @return bool|null
     */
    public function isSelfReadMeterOption(): ?bool
    {
        return $this->selfReadMeterOption;
    }

    /**
     * Sets specifiedReferralSource.
     *
     * @param string|null $specifiedReferralSource
     *
     * @return $this
     */
    public function setSpecifiedReferralSource(?string $specifiedReferralSource)
    {
        $this->specifiedReferralSource = $specifiedReferralSource;

        return $this;
    }

    /**
     * Gets specifiedReferralSource.
     *
     * @return string|null
     */
    public function getSpecifiedReferralSource(): ?string
    {
        return $this->specifiedReferralSource;
    }

    /**
     * Sets status.
     *
     * @param ApplicationRequestStatus $status
     *
     * @return $this
     */
    public function setStatus(ApplicationRequestStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return ApplicationRequestStatus
     */
    public function getStatus(): ApplicationRequestStatus
    {
        return $this->status;
    }

    /**
     * Gets statusHistory.
     *
     * @return string|null
     */
    public function getStatusHistory(): ?string
    {
        return null !== $this->id ? "/application_requests/$this->id/status_history" : null;
    }

    /**
     * Sets submitter.
     *
     * @param User|null $submitter
     */
    public function setSubmitter(?User $submitter)
    {
        $this->submitter = $submitter;
    }

    /**
     * Gets submitter.
     *
     * @return User|null
     */
    public function getSubmitter(): ?User
    {
        return $this->submitter;
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
     * Sets temporaryNumber.
     *
     * @param string|null $temporaryNumber
     *
     * @return $this
     */
    public function setTemporaryNumber(?string $temporaryNumber)
    {
        $this->temporaryNumber = $temporaryNumber;

        return $this;
    }

    /**
     * Gets temporaryNumber.
     *
     * @return string|null
     */
    public function getTemporaryNumber(): ?string
    {
        return $this->temporaryNumber;
    }

    /**
     * Sets terminationReason.
     *
     * @param string|null $terminationReason
     *
     * @return $this
     */
    public function setTerminationReason(?string $terminationReason)
    {
        $this->terminationReason = $terminationReason;

        return $this;
    }

    /**
     * Gets terminationReason.
     *
     * @return string|null
     */
    public function getTerminationReason(): ?string
    {
        return $this->terminationReason;
    }

    /**
     * Sets type.
     *
     * @param ApplicationRequestType $type
     *
     * @return $this
     */
    public function setType(ApplicationRequestType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return ApplicationRequestType
     */
    public function getType(): ApplicationRequestType
    {
        return $this->type;
    }

    /**
     * Get date to terminate contract.
     *
     * @return \DateTime|null
     */
    public function getTerminationDate()
    {
        return $this->terminationDate;
    }

    /**
     * Set date to terminate contract.
     *
     * @param \DateTime|null $terminationDate Date to terminate contract.
     *
     * @return self
     */
    public function setTerminationDate($terminationDate)
    {
        $this->terminationDate = $terminationDate;

        return $this;
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
}
