<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\AccountType;
use App\Enum\ActionStatus;
use App\Enum\ContactMethod;
use App\Enum\CustomerAccountStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use iter;

/**
 * Basic unit of information about a customer.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CustomerAccountRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"account_number"}),
 *     @ORM\Index(columns={"keywords"}),
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"customer_account_read"}},
 *     "denormalization_context"={"groups"={"customer_account_write"}},
 *     "filters"={
 *         "customer_account.date",
 *         "customer_account.exists",
 *         "customer_account.json_search",
 *         "customer_account.order",
 *         "customer_account.search",
 *     },
 * })
 */
class CustomerAccount
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
     * @var string|null The identifier of the customer account.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=true)
     * @ApiProperty()
     */
    protected $accountNumber;

    /**
     * @var Collection<Activity> The activity carried out on an item.
     *
     * @ORM\ManyToMany(targetEntity="Activity", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="customer_account_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="activity_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $activities;

    /**
     * @var Collection<CustomerAccountPostalAddress> The address table handling relationships between customer & postal address.
     *
     * @ORM\OneToMany(targetEntity="CustomerAccountPostalAddress", cascade={"persist"}, orphanRemoval=true, mappedBy="customerAccount")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $addresses;

    /**
     * @var Collection<ApplicationRequest> An application request.
     *
     * @ORM\OneToMany(targetEntity="ApplicationRequest", cascade={"persist"}, mappedBy="customer")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $applicationRequests;

    /**
     * @var string[] The account category.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $categories;

    /**
     * @var Collection<Contract> A contract.
     *
     * @ORM\OneToMany(targetEntity="Contract", cascade={"persist"}, mappedBy="customer")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $contracts;

    /**
     * @var Corporation|null Organization: A business corporation.
     *
     * @ORM\OneToOne(targetEntity="Corporation", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $corporationDetails;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $customerPortalEnabled;

    /**
     * @var \DateTime|null The date customer was added to the blacklist.
     *
     * @ORM\Column(type="date", nullable=true)
     * @ApiProperty()
     */
    protected $dateBlacklisted;

    /**
     * @var Contract|null The default Contract that will receive all credit earnings automatically.
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $defaultCreditsContract;

    /**
     * @var bool|null Determines whether the contact person should not be contacted.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $doNotContact;

    /**
     * @var string|null The external customer number.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $externalCustomerNumber;

    /**
     * @var DigitalDocument|null An electronic file or document.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $image;

    /**
     * @var string|null
     *
     * @ORM\Column(type="tsvector", nullable=true, options={
     *     "tsvector_fields"={
     *         "accountNumber"={
     *             "config"="english",
     *             "weight"="A",
     *         },
     *     },
     * })
     */
    protected $keywords;

    /**
     * @var bool|null Determines whether the lead is an LPG user.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $lpgUser;

    /**
     * @var Collection<UpdateCreditsAction> The act of updating the credits amount.
     *
     * @ORM\ManyToMany(targetEntity="UpdateCreditsAction", cascade={"persist"})
     * @ORM\JoinTable(
     *     name="customer_accounts_money_credits_actions",
     *     joinColumns={@ORM\JoinColumn(name="customer_account_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="money_credits_action_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $moneyCreditsActions;

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
     * @var Partner|null A partner.
     *
     * @ORM\OneToOne(targetEntity="Partner")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $partnerDetails;

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
     * @ORM\ManyToMany(targetEntity="UpdateCreditsAction", cascade={"persist"})
     * @ORM\JoinTable(
     *     name="customer_accounts_point_credits_actions",
     *     joinColumns={@ORM\JoinColumn(name="customer_account_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="point_credits_action_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $pointCreditsActions;

    //@todo Make it an array.
    /**
     * @var ContactMethod|null The preferred contact method of the person.
     *
     * @ORM\Column(type="contact_method_enum", nullable=true)
     * @ApiProperty()
     */
    protected $preferredContactMethod;

    /**
     * @var Collection<Quotation> A quotation.
     *
     * @ORM\OneToMany(targetEntity="Quotation", cascade={"persist"}, mappedBy="customer")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $quotations;

    /**
     * @var string|null A code used to refer someone.
     *
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @ApiProperty()
     */
    protected $referralCode;

    /**
     * @var Collection<CustomerAccountRelationship> The relationship between two customers.
     *
     * @ORM\ManyToMany(targetEntity="CustomerAccountRelationship", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="customer_account_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="relationship_id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $relationships;

    /**
     * @var CustomerAccountStatus The status of the customer account.
     *
     * @ORM\Column(type="customer_account_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var Collection<DigitalDocument> A file attached to the customer account.
     *
     * @ORM\ManyToMany(targetEntity="DigitalDocument", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="customer_account_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", unique=true, onDelete="CASCADE")},
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $supplementaryFiles;

    /**
     * @var bool|null Determines whether the lead is a tenant.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $tenant;

    /**
     * @var AccountType The account type.
     *
     * @ORM\Column(type="account_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var User|null A user.
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="customerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $user;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->applicationRequests = new ArrayCollection();
        $this->categories = [];
        $this->contracts = new ArrayCollection();
        $this->moneyCreditsActions = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->pointCreditsActions = new ArrayCollection();
        $this->quotations = new ArrayCollection();
        $this->relationships = new ArrayCollection();
        $this->supplementaryFiles = new ArrayCollection();
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
     * Sets accountNumber.
     *
     * @param string|null $accountNumber
     *
     * @return $this
     */
    public function setAccountNumber(?string $accountNumber)
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    /**
     * Gets accountNumber.
     *
     * @return string|null
     */
    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
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
     * @param CustomerAccountPostalAddress $address
     *
     * @return $this
     */
    public function addAddress(CustomerAccountPostalAddress $address)
    {
        $this->addresses[] = $address;

        return $this;
    }

    /**
     * Removes address.
     *
     * @param CustomerAccountPostalAddress $address
     *
     * @return $this
     */
    public function removeAddress(CustomerAccountPostalAddress $address)
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * Gets addresses.
     *
     * @return CustomerAccountPostalAddress[]
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
     * Adds category.
     *
     * @param string $category
     *
     * @return $this
     */
    public function addCategory(string $category)
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     * Removes category.
     *
     * @param string $category
     *
     * @return $this
     */
    public function removeCategory(string $category)
    {
        if (false !== ($key = \array_search($category, $this->categories, true))) {
            \array_splice($this->categories, $key, 1);
        }

        return $this;
    }

    /**
     * Gets categories.
     *
     * @return string[]
     */
    public function getCategories(): array
    {
        return $this->categories;
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
     * Gets customerPortalEnabled.
     *
     * @return bool|null
     */
    public function getCustomerPortalEnabled(): ?bool
    {
        return $this->customerPortalEnabled;
    }

    /**
     * Sets customerPortalEnabled.
     *
     * @param bool|null $customerPortalEnabled
     *
     * @return $this
     */
    public function setCustomerPortalEnabled(?bool $customerPortalEnabled)
    {
        $this->customerPortalEnabled = $customerPortalEnabled;

        return $this;
    }

    /**
     * Gets dateBlacklisted.
     *
     * @return \DateTime|null
     */
    public function getDateBlacklisted(): ?\DateTime
    {
        return $this->dateBlacklisted;
    }

    /**
     * Sets dateBlacklisted.
     *
     * @param \DateTime|null $dateBlacklisted
     *
     * @return $this
     */
    public function setDateBlacklisted(?\DateTime $dateBlacklisted)
    {
        $this->dateBlacklisted = $dateBlacklisted;

        return $this;
    }

    /**
     * Sets defaultCreditsContract.
     *
     * @param Contract|null $defaultCreditsContract
     *
     * @return $this
     */
    public function setDefaultCreditsContract(?Contract $defaultCreditsContract)
    {
        $this->defaultCreditsContract = $defaultCreditsContract;

        return $this;
    }

    /**
     * Gets defaultCreditsContract.
     *
     * @return Contract|null
     */
    public function getDefaultCreditsContract(): ?Contract
    {
        return $this->defaultCreditsContract;
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
     * Sets externalCustomerNumber.
     *
     * @param string|null $externalCustomerNumber
     *
     * @return $this
     */
    public function setExternalCustomerNumber(?string $externalCustomerNumber)
    {
        $this->externalCustomerNumber = $externalCustomerNumber;

        return $this;
    }

    /**
     * Gets externalCustomerNumber.
     *
     * @return string|null
     */
    public function getExternalCustomerNumber(): ?string
    {
        return $this->externalCustomerNumber;
    }

    /**
     * Sets image.
     *
     * @param DigitalDocument|null $image
     *
     * @return $this
     */
    public function setImage(?DigitalDocument $image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Gets image.
     *
     * @return DigitalDocument|null
     */
    public function getImage(): ?DigitalDocument
    {
        return $this->image;
    }

    /**
     * Gets lifetime money credits earnings.
     *
     * @return MonetaryAmount
     */
    public function getLifetimeMoneyCreditsEarnings(): MonetaryAmount
    {
        $lifetimeEarnings = iter\reduce(function (MonetaryAmount $lifetimeEarnings, UpdateCreditsAction $creditsAction, $i): MonetaryAmount {
            $currency = $lifetimeEarnings->getCurrency();
            $totalLifetimeEarnings = $lifetimeEarnings->getValue();

            if ($creditsAction->getCreditsTransaction() instanceof MoneyCreditsTransaction &&
                $creditsAction instanceof CreditsAdditionInterface &&
                ActionStatus::COMPLETED === $creditsAction->getStatus()->getValue()
            ) {
                $currency = $creditsAction->getCurrency();
                $totalLifetimeEarnings = \bcadd($totalLifetimeEarnings, $creditsAction->getAmount(), 2);
            }

            return new MonetaryAmount((string) $totalLifetimeEarnings, $currency);
        }, $this->moneyCreditsActions, new MonetaryAmount('0', 'SGD'));

        return $lifetimeEarnings;
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
     * Adds moneyCreditsAction.
     *
     * @param UpdateCreditsAction $moneyCreditsAction
     *
     * @return $this
     */
    public function addMoneyCreditsAction(UpdateCreditsAction $moneyCreditsAction)
    {
        $this->moneyCreditsActions[] = $moneyCreditsAction;

        return $this;
    }

    /**
     * Removes moneyCreditsAction.
     *
     * @param UpdateCreditsAction $moneyCreditsAction
     *
     * @return $this
     */
    public function removeMoneyCreditsAction(UpdateCreditsAction $moneyCreditsAction)
    {
        $this->moneyCreditsActions->removeElement($moneyCreditsAction);

        return $this;
    }

    /**
     * Gets moneyCreditsActions.
     *
     * @return UpdateCreditsAction[]
     */
    public function getMoneyCreditsActions(): array
    {
        return $this->moneyCreditsActions->getValues();
    }

    /**
     * Gets money credits balance.
     *
     * @return MonetaryAmount
     */
    public function getMoneyCreditsBalance(): MonetaryAmount
    {
        $now = new \DateTime();

        $balance = iter\reduce(function (MonetaryAmount $balance, UpdateCreditsAction $creditsAction, $i) use ($now): MonetaryAmount {
            $transaction = $creditsAction->getCreditsTransaction();
            if ($transaction instanceof MoneyCreditsTransaction) {
                $currency = $creditsAction->getCurrency();
                $newBalance = $balance->getValue();

                if (
                    ActionStatus::COMPLETED === $creditsAction->getStatus()->getValue() &&
                    $creditsAction->getStartTime() <= $now &&
                    true === $transaction->isValid()
                ) {
                    $transactionAmount = $transaction->getAmount()->getValue();

                    if ($creditsAction instanceof CreditsAdditionInterface) {
                        $newBalance = \bcadd($newBalance, $transactionAmount, 2);
                    } elseif ($creditsAction instanceof CreditsSubtractionInterface) {
                        $newBalance = \bcsub($newBalance, $transactionAmount, 2);
                    } elseif ($creditsAction instanceof CreditsExpirationInterface) {
                        $newBalance = \bcsub($newBalance, \bcsub($transactionAmount, $creditsAction->getAmountUsed(), 2), 2);
                    }
                }

                $balance = new MonetaryAmount((string) $newBalance, $currency);
            }

            return $balance;
        }, $this->moneyCreditsActions, new MonetaryAmount('0', 'SGD'));

        return $balance;
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
     * Sets partnerDetails.
     *
     * @param Partner|null $partnerDetails
     *
     * @return $this
     */
    public function setPartnerDetails(?Partner $partnerDetails)
    {
        $this->partnerDetails = $partnerDetails;

        return $this;
    }

    /**
     * Gets partnerDetails.
     *
     * @return Partner|null
     */
    public function getPartnerDetails(): ?Partner
    {
        return $this->partnerDetails;
    }

    /**
     * Gets pending money credits earnings.
     *
     * @return MonetaryAmount
     */
    public function getPendingMoneyCreditsEarnings(): MonetaryAmount
    {
        $pendingEarnings = iter\reduce(function (MonetaryAmount $pendingEarnings, UpdateCreditsAction $creditsAction, $i): MonetaryAmount {
            $currency = $pendingEarnings->getCurrency();
            $totalPendingEarnings = $pendingEarnings->getValue();

            if ($creditsAction->getCreditsTransaction() instanceof MoneyCreditsTransaction &&
                $creditsAction instanceof EarnCustomerAffiliateCreditsAction &&
                ActionStatus::IN_PROGRESS === $creditsAction->getStatus()->getValue()
            ) {
                $currency = $creditsAction->getCurrency();
                $totalPendingEarnings = \bcadd($totalPendingEarnings, $creditsAction->getAmount(), 2);
            }

            return new MonetaryAmount((string) $totalPendingEarnings, $currency);
        }, $this->moneyCreditsActions, new MonetaryAmount('0', 'SGD'));

        return $pendingEarnings;
    }

    /**
     * Gets pending point credits earnings.
     *
     * @return QuantitativeValue
     */
    public function getPendingPointCreditsEarnings(): QuantitativeValue
    {
        $pendingEarnings = iter\reduce(function (string $pendingEarnings, UpdateCreditsAction $creditsAction, $i): string {
            if ($creditsAction->getCreditsTransaction() instanceof PointCreditsTransaction &&
                $creditsAction instanceof EarnCustomerAffiliateCreditsAction &&
                ActionStatus::IN_PROGRESS === $creditsAction->getStatus()->getValue()
            ) {
                $pendingEarnings += $creditsAction->getAmount();
            }

            return (string) $pendingEarnings;
        }, $this->pointCreditsActions, '0');

        return new QuantitativeValue($pendingEarnings);
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
                    true === $transaction->isValid()
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
     * Adds quotation.
     *
     * @param Quotation $quotation
     *
     * @return $this
     */
    public function addQuotation(Quotation $quotation)
    {
        $this->quotations[] = $quotation;
        $quotation->setCustomer($this);

        return $this;
    }

    /**
     * Removes quotation.
     *
     * @param Quotation $quotation
     *
     * @return $this
     */
    public function removeQuotation(Quotation $quotation)
    {
        $this->quotations->removeElement($quotation);

        return $this;
    }

    /**
     * Gets quotations.
     *
     * @return Quotation[]
     */
    public function getQuotations(): array
    {
        return $this->quotations->getValues();
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
     * Adds relationship.
     *
     * @param CustomerAccountRelationship $relationship
     *
     * @return $this
     */
    public function addRelationship(CustomerAccountRelationship $relationship)
    {
        $this->relationships[] = $relationship;

        return $this;
    }

    /**
     * Removes relationship.
     *
     * @param CustomerAccountRelationship $relationship
     *
     * @return $this
     */
    public function removeRelationship(CustomerAccountRelationship $relationship)
    {
        $this->relationships->removeElement($relationship);

        return $this;
    }

    /**
     * Gets relationships.
     *
     * @return CustomerAccountRelationship[]
     */
    public function getRelationships(): array
    {
        return $this->relationships->getValues();
    }

    /**
     * Sets status.
     *
     * @param CustomerAccountStatus $status
     *
     * @return $this
     */
    public function setStatus(CustomerAccountStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return CustomerAccountStatus
     */
    public function getStatus(): CustomerAccountStatus
    {
        return $this->status;
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
     * Gets total money credits withdrawn.
     *
     * @return MonetaryAmount
     */
    public function getTotalMoneyCreditsWithdrawn(): MonetaryAmount
    {
        $now = new \DateTime();

        $totalWithdrawn = iter\reduce(function (MonetaryAmount $totalWithdrawn, UpdateCreditsAction $creditsAction, $i): MonetaryAmount {
            $currency = $totalWithdrawn->getCurrency();
            $withdrawn = $totalWithdrawn->getValue();

            if ($creditsAction instanceof WithdrawCreditsAction &&
                $creditsAction->getCreditsTransaction() instanceof MoneyCreditsTransaction &&
                ActionStatus::COMPLETED === $creditsAction->getStatus()->getValue()
            ) {
                $withdrawn = \bcadd($withdrawn, $creditsAction->getCreditsTransaction()->getAmount()->getValue(), 2);
            }

            return new MonetaryAmount((string) $withdrawn, $currency);
        }, $this->moneyCreditsActions, new MonetaryAmount('0', 'SGD'));

        return $totalWithdrawn;
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

    /**
     * Sets user.
     *
     * @param User|null $user
     *
     * @return $this
     */
    public function setUser(?User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Gets user.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
}
