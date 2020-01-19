<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\AffiliateCommissionStatus;
use App\Enum\AffiliateWebServicePartner;
use Doctrine\ORM\Mapping as ORM;

/**
 * An affiliate program transaction.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"affiliate_program_transaction_read"}},
 *     "denormalization_context"={"groups"={"affiliate_program_transaction_write"}},
 * })
 */
class AffiliateProgramTransaction
{
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
     * @var AffiliateProgram|null An affiliate program.
     *
     * @ORM\ManyToOne(targetEntity="AffiliateProgram")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $affiliateProgram;

    /**
     * @var MonetaryAmount The commission amount.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $commissionAmount;

    /**
     * @var AffiliateCommissionStatus The commission status.
     *
     * @ORM\Column(type="affiliate_commission_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $commissionStatus;

    /**
     * @var CustomerAccount|null Party placing the order or paying the invoice.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/customer")
     */
    protected $customer;

    /**
     * @var AffiliateProgramCommissionConfiguration|null Commission configuration used for calculation.
     *
     * @ORM\ManyToOne(targetEntity="AffiliateProgramCommissionConfiguration")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $moneyCommissionConfiguration;

    /**
     * @var MonetaryAmount Money credits to be awarded from the transaction.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $moneyCreditsAmount;

    /**
     * @var MonetaryAmount The order amount.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $orderAmount;

    /**
     * @var AffiliateProgramCommissionConfiguration|null Commission configuration used for calculation.
     *
     * @ORM\ManyToOne(targetEntity="AffiliateProgramCommissionConfiguration")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $pointCommissionConfiguration;

    /**
     * @var QuantitativeValue Point credits to be awarded from the transaction.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $pointCreditsAmount;

    /**
     * @var PointCreditsExchangeRate|null Reference to the exchange rate used at the time of conversion.
     *
     * @ORM\ManyToOne(targetEntity="PointCreditsExchangeRate")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $pointCreditsExchangeRate;

    /**
     * @var AffiliateWebServicePartner The service provider, service operator, or service performer; the goods producer. Another party (a seller) may offer those services or goods on behalf of the provider. A provider may also serve as the seller.
     *
     * @ORM\Column(type="affiliate_web_service_partner_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/provider")
     */
    protected $provider;

    /**
     * @var \DateTime|null The transaction date returned from affiliate APIs.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $transactionDate;

    /**
     * @var string The transaction number.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty()
     */
    protected $transactionNumber;

    public function __construct()
    {
        $this->commissionAmount = new MonetaryAmount();
        $this->moneyCreditsAmount = new MonetaryAmount();
        $this->orderAmount = new MonetaryAmount();
        $this->pointCreditsAmount = new QuantitativeValue();
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
     * Sets affiliateProgram.
     *
     * @param AffiliateProgram|null $affiliateProgram
     *
     * @return $this
     */
    public function setAffiliateProgram(?AffiliateProgram $affiliateProgram)
    {
        $this->affiliateProgram = $affiliateProgram;

        return $this;
    }

    /**
     * Gets affiliateProgram.
     *
     * @return AffiliateProgram|null
     */
    public function getAffiliateProgram(): ?AffiliateProgram
    {
        return $this->affiliateProgram;
    }

    /**
     * Sets commissionAmount.
     *
     * @param MonetaryAmount $commissionAmount
     *
     * @return $this
     */
    public function setCommissionAmount(MonetaryAmount $commissionAmount)
    {
        $this->commissionAmount = $commissionAmount;

        return $this;
    }

    /**
     * Gets commissionAmount.
     *
     * @return MonetaryAmount
     */
    public function getCommissionAmount(): MonetaryAmount
    {
        return $this->commissionAmount;
    }

    /**
     * Sets commissionStatus.
     *
     * @param AffiliateCommissionStatus $commissionStatus
     *
     * @return $this
     */
    public function setCommissionStatus(AffiliateCommissionStatus $commissionStatus)
    {
        $this->commissionStatus = $commissionStatus;

        return $this;
    }

    /**
     * Gets commissionStatus.
     *
     * @return AffiliateCommissionStatus
     */
    public function getCommissionStatus(): AffiliateCommissionStatus
    {
        return $this->commissionStatus;
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
     * Sets moneyCommissionConfiguration.
     *
     * @param AffiliateProgramCommissionConfiguration|null $moneyCommissionConfiguration
     *
     * @return $this
     */
    public function setMoneyCommissionConfiguration(?AffiliateProgramCommissionConfiguration $moneyCommissionConfiguration)
    {
        $this->moneyCommissionConfiguration = $moneyCommissionConfiguration;

        return $this;
    }

    /**
     * Gets moneyCommissionConfiguration.
     *
     * @return AffiliateProgramCommissionConfiguration|null
     */
    public function getMoneyCommissionConfiguration(): ?AffiliateProgramCommissionConfiguration
    {
        return $this->moneyCommissionConfiguration;
    }

    /**
     * Sets moneyCreditsAmount.
     *
     * @param MonetaryAmount $moneyCreditsAmount
     *
     * @return $this
     */
    public function setMoneyCreditsAmount(MonetaryAmount $moneyCreditsAmount)
    {
        $this->moneyCreditsAmount = $moneyCreditsAmount;

        return $this;
    }

    /**
     * Gets moneyCreditsAmount.
     *
     * @return MonetaryAmount
     */
    public function getMoneyCreditsAmount(): MonetaryAmount
    {
        return $this->moneyCreditsAmount;
    }

    /**
     * Sets orderAmount.
     *
     * @param MonetaryAmount $orderAmount
     *
     * @return $this
     */
    public function setOrderAmount(MonetaryAmount $orderAmount)
    {
        $this->orderAmount = $orderAmount;

        return $this;
    }

    /**
     * Gets orderAmount.
     *
     * @return MonetaryAmount
     */
    public function getOrderAmount(): MonetaryAmount
    {
        return $this->orderAmount;
    }

    /**
     * Sets pointCommissionConfiguration.
     *
     * @param AffiliateProgramCommissionConfiguration|null $pointCommissionConfiguration
     *
     * @return $this
     */
    public function setPointCommissionConfiguration(?AffiliateProgramCommissionConfiguration $pointCommissionConfiguration)
    {
        $this->pointCommissionConfiguration = $pointCommissionConfiguration;

        return $this;
    }

    /**
     * Gets pointCommissionConfiguration.
     *
     * @return AffiliateProgramCommissionConfiguration|null
     */
    public function getPointCommissionConfiguration(): ?AffiliateProgramCommissionConfiguration
    {
        return $this->pointCommissionConfiguration;
    }

    /**
     * Sets pointCreditsAmount.
     *
     * @param QuantitativeValue $pointCreditsAmount
     *
     * @return $this
     */
    public function setPointCreditsAmount(QuantitativeValue $pointCreditsAmount)
    {
        $this->pointCreditsAmount = $pointCreditsAmount;

        return $this;
    }

    /**
     * Gets pointCreditsAmount.
     *
     * @return QuantitativeValue
     */
    public function getPointCreditsAmount(): QuantitativeValue
    {
        return $this->pointCreditsAmount;
    }

    /**
     * Sets pointCreditsExchangeRate.
     *
     * @param PointCreditsExchangeRate|null $pointCreditsExchangeRate
     *
     * @return $this
     */
    public function setPointCreditsExchangeRate(?PointCreditsExchangeRate $pointCreditsExchangeRate)
    {
        $this->pointCreditsExchangeRate = $pointCreditsExchangeRate;

        return $this;
    }

    /**
     * Gets pointCreditsExchangeRate.
     *
     * @return PointCreditsExchangeRate|null
     */
    public function getPointCreditsExchangeRate(): ?PointCreditsExchangeRate
    {
        return $this->pointCreditsExchangeRate;
    }

    /**
     * Sets provider.
     *
     * @param AffiliateWebServicePartner $provider
     *
     * @return $this
     */
    public function setProvider(AffiliateWebServicePartner $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Gets provider.
     *
     * @return AffiliateWebServicePartner
     */
    public function getProvider(): AffiliateWebServicePartner
    {
        return $this->provider;
    }

    /**
     * Sets transactionDate.
     *
     * @param \DateTime|null $transactionDate
     *
     * @return $this
     */
    public function setTransactionDate(?\DateTime $transactionDate)
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    /**
     * Gets transactionDate.
     *
     * @return \DateTime|null
     */
    public function getTransactionDate(): ?\DateTime
    {
        return $this->transactionDate;
    }

    /**
     * Sets transactionNumber.
     *
     * @param string $transactionNumber
     *
     * @return $this
     */
    public function setTransactionNumber(string $transactionNumber)
    {
        $this->transactionNumber = $transactionNumber;

        return $this;
    }

    /**
     * Gets transactionNumber.
     *
     * @return string
     */
    public function getTransactionNumber(): string
    {
        return $this->transactionNumber;
    }
}
