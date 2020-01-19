<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\AccountType;
use App\Enum\ActionStatus;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Enum\MeterType;
use App\Enum\PaymentMode;
use App\Enum\RefundType;
use App\WebService\Billing\Controller\ContractAccountsReceivableInvoiceAttachmentController;
use App\WebService\Billing\Controller\ContractAccountsReceivableInvoiceController;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use iter;

/**
 * A contract.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"contract_read"}},
 *     "denormalization_context"={"groups"={"contract_write", "contract_activation_create"}},
 *     "filters"={
 *         "contract.date",
 *         "contract.exists",
 *         "contract.json_search",
 *         "contract.order",
 *         "contract.search",
 *     },
 * },
 * itemOperations={
 *     "get",
 *     "get_accounts_receivable_invoice"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/accounts_receivable_invoices/{invoiceNumber}.{_format}",
 *         "controller"=ContractAccountsReceivableInvoiceController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"accounts_receivable_invoice_read"}}
 *     },
 *     "get_accounts_receivable_invoice_attachment"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/accounts_receivable_invoices/{invoiceNumber}/attachment.{_format}",
 *         "controller"=ContractAccountsReceivableInvoiceAttachmentController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"accounts_receivable_invoice_attachment_read"}}
 *     },
 *     "put"
 * })
 */
class Contract
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
     * @var Collection<ContractAction> An action performed by a direct agent and indirect participants upon a direct object. Optionally happens at a location with the help of an inanimate instrument. The execution of the action may produce a result. Specific action sub-type documentation specifies the exact expectation of each argument/role.
     *
     * @ORM\ManyToMany(targetEntity="ContractAction", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="contract_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="action_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $actions;

    /**
     * @var Collection<AddonService> An add-on service.
     *
     * @ORM\ManyToMany(targetEntity="AddonService", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="contract_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="addon_service_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $addonServices;

    /**
     * @var Collection<ContractPostalAddress> The address.
     *
     * @ORM\OneToMany(targetEntity="ContractPostalAddress", cascade={"persist"}, mappedBy="contract")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $addresses;

    /**
     * @var QuantitativeValue The average consumption of electricty.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $averageConsumption;

    /**
     * @var string|null The billing period ID of the contract.
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     * @ApiProperty()
     */
    protected $billingPeriodId;

    /**
     * @var string[] The bill subscription type.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $billSubscriptionTypes;

    /**
     * @var QuantitativeValue The contract closure notice period.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $closureNoticePeriod;

    /**
     * @var CustomerAccount The contact person for the contract.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $contactPerson;

    /**
     * @var string|null The identifier of the contract.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=true)
     * @ApiProperty()
     */
    protected $contractNumber;

    /**
     * @var QuantitativeValue The contract period.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $contractPeriod;

    /**
     * @var Corporation|null Organization: A business corporation.
     *
     * @ORM\OneToOne(targetEntity="Corporation", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $corporationDetails;

    /**
     * @var CustomerAccount Party placing the order or paying the invoice.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount", inversedBy="contracts")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/customer")
     */
    protected $customer;

    /**
     * @var AccountType|null The customer account type.
     *
     * @ORM\Column(type="account_type_enum", nullable=true)
     * @ApiProperty()
     */
    protected $customerType;

    /**
     * @var bool Determines whether the contract has been customized.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @ApiProperty()
     */
    protected $customized;

    /**
     * @var MonetaryAmount The deposit amount.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $depositAmount;

    /**
     * @var RefundType|null The refund type for the contract.
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
     * @var \DateTime|null The end date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/endDate")
     */
    protected $endDate;

    /**
     * @var Collection<InternalDocument> Hardcopy version of the contract.
     *
     * @ORM\ManyToMany(targetEntity="InternalDocument", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="contract_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", unique=true, onDelete="CASCADE")},
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $files;

    /**
     * @var bool|null Determines whether GIRO option is enabled.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $giroOption;

    /**
     * @var string|null The location of for example where the event is happening, an organization is located, or where an action takes place.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty(iri="http://schema.org/location")
     */
    protected $location;

    /**
     * @var \DateTime|null The contract lock in date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $lockInDate;

    /**
     * @var MeterType|null The meter type of the lead.
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
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var Collection<Payment> A payment.
     *
     * @ORM\ManyToMany(targetEntity="Payment", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="contract_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="payment_id", unique=true, onDelete="CASCADE")},
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $payments;

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
     * @ORM\OneToOne(targetEntity="Person", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $personDetails;

    /**
     * @var Collection<UpdateCreditsAction> The act of updating the credits amount.
     *
     * @ORM\ManyToMany(targetEntity="UpdateCreditsAction", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     name="contracts_point_credits_actions",
     *     joinColumns={@ORM\JoinColumn(name="contract_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="point_credits_action_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $pointCreditsActions;

    /**
     * @var bool|null Determines whether the recurring option is enabled.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $recurringOption;

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
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty()
     */
    protected $remark;

    /**
     * @var bool|null Determines whether the contract is applied by the customer.
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
     * @var \DateTime|null The start date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/startDate")
     */
    protected $startDate;

    /**
     * @var ContractStatus The status of the contract.
     *
     * @ORM\Column(type="contract_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var string|null The contract subtype.
     *
     * @ORM\Column(type="contract_subtype_enum", nullable=true)
     * @ApiProperty()
     */
    protected $subtype;

    /**
     * @var Collection<DigitalDocument> A file attached to the contract.
     *
     * @ORM\ManyToMany(targetEntity="DigitalDocument", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="contract_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", unique=true, onDelete="CASCADE")},
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $supplementaryFiles;

    /**
     * @var TariffRate|null The tariff rate applied to this contract.
     *
     * @ORM\ManyToOne(targetEntity="TariffRate", inversedBy="contracts", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $tariffRate;

    /**
     * @var ContractType The contract type.
     *
     * @ORM\Column(type="contract_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    public function __construct()
    {
        $this->actions = new ArrayCollection();
        $this->addonServices = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->averageConsumption = new QuantitativeValue();
        $this->billSubscriptionTypes = [];
        $this->closureNoticePeriod = new QuantitativeValue();
        $this->contractPeriod = new QuantitativeValue();
        $this->depositAmount = new MonetaryAmount();
        $this->files = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->pointCreditsActions = new ArrayCollection();
        $this->supplementaryFiles = new ArrayCollection();
    }

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
            $this->actions = new ArrayCollection();
            $this->pointCreditsActions = new ArrayCollection();
            $this->refundee = null;
            $this->refundeeDetails = null;
            $this->payments = new ArrayCollection();
            $this->supplementaryFiles = new ArrayCollection();
            $this->files = new ArrayCollection();

            $addonsServices = new ArrayCollection();
            foreach ($this->addonServices as $addonsService) {
                $addonsServices[] = clone $addonsService;
            }
            $this->addonServices = $addonsServices;

            $addresses = new ArrayCollection();
            foreach ($this->addresses as $address) {
                $tempAddress = clone $address;
                $tempAddress->setContract($this);
                $addresses[] = $tempAddress;
            }
            $this->addresses = $addresses;

            if (null !== $this->corporationDetails) {
                $corporationDetails = clone $this->corporationDetails;
                $this->setCorporationDetails($corporationDetails);
            }

            if (null !== $this->endDate) {
                $endDate = clone $this->endDate;
                $this->setEndDate($endDate);
            }

            if (null !== $this->lockInDate) {
                $lockInDate = clone $this->lockInDate;
                $this->setLockInDate($lockInDate);
            }

            if (null !== $this->personDetails) {
                $personDetails = clone $this->personDetails;
                $this->personDetails = $personDetails;
            }

            if (null !== $this->startDate) {
                $startDate = clone $this->startDate;
                $this->setStartDate($startDate);
            }

            if (null !== $this->tariffRate) {
                $tariffRate = clone $this->tariffRate;
                $tariffRate->addContract($this);
                $this->setTariffRate($tariffRate);
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
     * Adds action.
     *
     * @param ContractAction $action
     *
     * @return $this
     */
    public function addAction(ContractAction $action)
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * Removes action.
     *
     * @param ContractAction $action
     *
     * @return $this
     */
    public function removeAction(ContractAction $action)
    {
        $this->actions->removeElement($action);

        return $this;
    }

    /**
     * Get actions.
     *
     * @return ContractAction[]
     */
    public function getActions(): array
    {
        return $this->actions->getValues();
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
     * @param ContractPostalAddress $address
     *
     * @return $this
     */
    public function addAddress(ContractPostalAddress $address)
    {
        $this->addresses[] = $address;

        return $this;
    }

    /**
     * Removes address.
     *
     * @param ContractPostalAddress $address
     *
     * @return $this
     */
    public function removeAddress(ContractPostalAddress $address)
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * Get addresses.
     *
     * @return ContractPostalAddress[]
     */
    public function getAddresses(): array
    {
        return $this->addresses->getValues();
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
     * Sets billingPeriodId.
     *
     * @param string|null $billingPeriodId
     *
     * @return $this
     */
    public function setBillingPeriodId(?string $billingPeriodId)
    {
        $this->billingPeriodId = $billingPeriodId;

        return $this;
    }

    /**
     * Gets billingPeriodId.
     *
     * @return string|null
     */
    public function getBillingPeriodId(): ?string
    {
        return $this->billingPeriodId;
    }

    /**
     * Gets arrearsHistory.
     *
     * @return string|null
     */
    public function getArrearsHistory(): ?string
    {
        return null !== $this->id ? "/contracts/$this->id/arrears_histories" : null;
    }

    /**
     * Gets billingInformation.
     *
     * @return string|null
     */
    public function getBillingInformation(): ?string
    {
        return null !== $this->id ? "/contracts/$this->id/billing_information" : null;
    }

    /**
     * Gets billingSummary.
     *
     * @return string|null
     */
    public function getBillingSummary(): ?string
    {
        return null !== $this->id ? "/contracts/$this->id/billing_summary" : null;
    }

    /**
     * Gets ConsumptionByBillingPeriod.
     *
     * @return string|null
     */
    public function getConsumptionHistory(): ?string
    {
        return null !== $this->id ? "/contracts/$this->id/consumption_histories" : null;
    }

    /**
     * Gets EmailMessageHistory.
     *
     * @return string|null
     */
    public function getEmailMessageHistory(): ?string
    {
        return null !== $this->id ? "/contracts/$this->id/email_message_histories" : null;
    }

    /**
     * Gets FinancialHistory.
     *
     * @return string|null
     */
    public function getFinancialHistory(): ?string
    {
        return null !== $this->id ? "/contracts/$this->id/financial_histories" : null;
    }

    /**
     * Gets GiroHistory.
     *
     * @return string|null
     */
    public function getGiroHistory(): ?string
    {
        return null !== $this->id ? "/contracts/$this->id/giro_histories" : null;
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
     * Replaces billSubscriptionTypes.
     *
     * @param array $billSubscriptionTypes
     *
     * @return $this
     */
    public function replaceBillSubscriptionTypes(array $billSubscriptionTypes)
    {
        $this->billSubscriptionTypes = $billSubscriptionTypes;

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
     * Sets closureNoticePeriod.
     *
     * @param QuantitativeValue $closureNoticePeriod
     *
     * @return $this
     */
    public function setClosureNoticePeriod(QuantitativeValue $closureNoticePeriod)
    {
        $this->closureNoticePeriod = $closureNoticePeriod;

        return $this;
    }

    /**
     * Gets closureNoticePeriod.
     *
     * @return QuantitativeValue
     */
    public function getClosureNoticePeriod(): QuantitativeValue
    {
        return $this->closureNoticePeriod;
    }

    /**
     * Sets contactPerson.
     *
     * @param CustomerAccount $contactPerson
     *
     * @return $this
     */
    public function setContactPerson(CustomerAccount $contactPerson)
    {
        $this->contactPerson = $contactPerson;

        return $this;
    }

    /**
     * Gets contactPerson.
     *
     * @return CustomerAccount
     */
    public function getContactPerson(): CustomerAccount
    {
        return $this->contactPerson;
    }

    /**
     * Sets contractNumber.
     *
     * @param string|null $contractNumber
     *
     * @return $this
     */
    public function setContractNumber(?string $contractNumber)
    {
        $this->contractNumber = $contractNumber;

        return $this;
    }

    /**
     * Gets contractNumber.
     *
     * @return string|null
     */
    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    /**
     * Sets contractPeriod.
     *
     * @param QuantitativeValue $contractPeriod
     *
     * @return $this
     */
    public function setContractPeriod(QuantitativeValue $contractPeriod)
    {
        $this->contractPeriod = $contractPeriod;

        return $this;
    }

    /**
     * Gets contractPeriod.
     *
     * @return QuantitativeValue
     */
    public function getContractPeriod(): QuantitativeValue
    {
        return $this->contractPeriod;
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
     * @param CustomerAccount $customer
     *
     * @return $this
     */
    public function setCustomer(CustomerAccount $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Gets customer.
     *
     * @return CustomerAccount
     */
    public function getCustomer(): CustomerAccount
    {
        return $this->customer;
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
     * @param bool $customized
     *
     * @return $this
     */
    public function setCustomized(bool $customized)
    {
        $this->customized = $customized;

        return $this;
    }

    /**
     * Gets customized.
     *
     * @return bool
     */
    public function isCustomized(): bool
    {
        return $this->customized;
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
     * Sets endDate.
     *
     * @param \DateTime|null $endDate
     *
     * @return $this
     */
    public function setEndDate(?\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Gets endDate.
     *
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * Adds file.
     *
     * @param InternalDocument $file
     *
     * @return $this
     */
    public function addFile(InternalDocument $file)
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * Removes file.
     *
     * @param InternalDocument $file
     *
     * @return $this
     */
    public function removeFile(InternalDocument $file)
    {
        $this->files->removeElement($file);

        return $this;
    }

    /**
     * Gets files.
     *
     * @return InternalDocument[]
     */
    public function getFiles(): array
    {
        return $this->files->getValues();
    }

    /**
     * Sets giroOption.
     *
     * @param bool|null $giroOption
     *
     * @return $this
     */
    public function setGiroOption(?bool $giroOption)
    {
        $this->giroOption = $giroOption;

        return $this;
    }

    /**
     * Gets giroOption.
     *
     * @return bool|null
     */
    public function isGiroOption(): ?bool
    {
        return $this->giroOption;
    }

    /**
     * Gets lifetime point credits earnings.
     *
     * @return QuantitativeValue
     */
    public function getLifetimePointCreditsEarnings(): QuantitativeValue
    {
        $lifetimeEarnings = iter\reduce(function (string $lifetimeEarnings, UpdateCreditsAction $creditsAction, $i): string {
            if ($creditsAction->getCreditsTransaction() instanceof PointCreditsTransaction &&
                $creditsAction instanceof CreditsAdditionInterface &&
                ActionStatus::COMPLETED === $creditsAction->getStatus()->getValue()
            ) {
                $lifetimeEarnings += $creditsAction->getAmount();
            }

            return (string) $lifetimeEarnings;
        }, $this->pointCreditsActions, '0');

        return new QuantitativeValue($lifetimeEarnings);
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
     * Sets lockInDate.
     *
     * @param \DateTime|null $lockInDate
     *
     * @return $this
     */
    public function setLockInDate(?\DateTime $lockInDate)
    {
        $this->lockInDate = $lockInDate;

        return $this;
    }

    /**
     * Gets lockInDate.
     *
     * @return \DateTime|null
     */
    public function getLockInDate(): ?\DateTime
    {
        return $this->lockInDate;
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
     * Adds payment.
     *
     * @param Payment $payment
     *
     * @return $this
     */
    public function addPayment(Payment $payment)
    {
        $this->payments[] = $payment;

        return $this;
    }

    /**
     * Removes payment.
     *
     * @param Payment $payment
     *
     * @return $this
     */
    public function removePayment(Payment $payment)
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * Gets payments.
     *
     * @return Payment[]
     */
    public function getPayments(): array
    {
        return $this->payments->getValues();
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
     * Adds pointCreditsAction.
     *
     * @param UpdateCreditsAction $pointCreditsAction
     *
     * @return $this
     */
    public function addPointCreditsAction(UpdateCreditsAction $pointCreditsAction)
    {
        $this->pointCreditsActions[] = $pointCreditsAction;

        return $this;
    }

    /**
     * Removes pointCreditsAction.
     *
     * @param UpdateCreditsAction $pointCreditsAction
     *
     * @return $this
     */
    public function removePointCreditsAction(UpdateCreditsAction $pointCreditsAction)
    {
        $this->pointCreditsActions->removeElement($pointCreditsAction);

        return $this;
    }

    /**
     * Gets pointCreditsActions.
     *
     * @return UpdateCreditsAction[]
     */
    public function getPointCreditsActions(): array
    {
        return $this->pointCreditsActions->getValues();
    }

    /**
     * Gets point credits balance.
     *
     * @return QuantitativeValue
     */
    public function getPointCreditsBalance(): QuantitativeValue
    {
        $now = new \DateTime();

        $balance = iter\reduce(function (string $balance, UpdateCreditsAction $creditsAction, $i) use ($now): string {
            $transaction = $creditsAction->getCreditsTransaction();
            if ($transaction instanceof PointCreditsTransaction) {
                $transactionAmount = $transaction->getAmount()->getValue();

                if (
                    ActionStatus::COMPLETED === $creditsAction->getStatus()->getValue() &&
                    $creditsAction->getStartTime() <= $now &&
                    $transaction->getValidFrom() <= $now
                ) {
                    if ($creditsAction instanceof CreditsAdditionInterface) {
                        $balance += $transactionAmount;
                    } elseif ($creditsAction instanceof CreditsSubtractionInterface) {
                        $balance -= $transactionAmount;
                    }
                }
            }

            return (string) $balance;
        }, $this->pointCreditsActions, '0');

        return new QuantitativeValue($balance);
    }

    /**
     * Gets RccsHistory.
     *
     * @return string|null
     */
    public function getRccsHistory(): ?string
    {
        return null !== $this->id ? "/contracts/$this->id/rccs_histories" : null;
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
     * @param ContractStatus $status
     *
     * @return $this
     */
    public function setStatus(ContractStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return ContractStatus
     */
    public function getStatus(): ContractStatus
    {
        return $this->status;
    }

    /**
     * Sets subtype.
     *
     * @param string|null $subtype
     *
     * @return $this
     */
    public function setSubtype(?string $subtype)
    {
        $this->subtype = $subtype;

        return $this;
    }

    /**
     * Gets subtype.
     *
     * @return string|null
     */
    public function getSubtype(): ?string
    {
        return $this->subtype;
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
     * Sets type.
     *
     * @param ContractType $type
     *
     * @return $this
     */
    public function setType(ContractType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return ContractType
     */
    public function getType(): ContractType
    {
        return $this->type;
    }

    /**
     * Gets welcomePackages.
     *
     * @return string|null
     */
    public function getWelcomePackages(): ?string
    {
        return null !== $this->id ? "/contracts/$this->id/welcome_packages" : null;
    }
}
