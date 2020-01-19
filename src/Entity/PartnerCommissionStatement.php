<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\CommissionStatementStatus;
use App\Enum\PaymentStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A commission statement generated for a partner.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"partner_commission_statement_read"}},
 *     "denormalization_context"={"groups"={"partner_commission_statement_write"}},
 * })
 */
class PartnerCommissionStatement
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
     * @var string The bank account number.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bankAccountNumber;

    /**
     * @var string The bank name.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bankName;

    /**
     * @var string A number that confirms the given order or payment has been received.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/confirmationNumber")
     */
    protected $confirmationNumber;

    /**
     * @var Collection<PartnerCommissionStatementData> The commission rate data.
     *
     * @ORM\OneToMany(targetEntity="PartnerCommissionStatementData", cascade={"persist"}, mappedBy="statement", orphanRemoval=true)
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $data;

    /**
     * @var \DateTime The end date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty(iri="http://schema.org/endDate")
     */
    protected $endDate;

    /**
     * @var InternalDocument|null A softcopy of the statement to be printed.
     *
     * @ORM\OneToOne(targetEntity="InternalDocument")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $file;

    /**
     * @var Partner The partner that the statement is generated for.
     *
     * @ORM\ManyToOne(targetEntity="Partner", inversedBy="commissionStatements", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $partner;

    /**
     * @var string The name of the credit card or other method of payment for the order.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/paymentMethod")
     */
    protected $paymentMethod;

    /**
     * @var DigitalDocument|null A softcopy of the receipt for the payment made as proof of payment.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $paymentReceipt;

    /**
     * @var PaymentStatus The status of payment; whether the invoice has been paid or not.
     *
     * @ORM\Column(type="payment_status_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/paymentStatus")
     */
    protected $paymentStatus;

    /**
     * @var \DateTime The start date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty(iri="http://schema.org/startDate")
     */
    protected $startDate;

    /**
     * @var string The identifier of the statement.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=false)
     * @ApiProperty()
     */
    protected $statementNumber;

    /**
     * @var CommissionStatementStatus The status of the statement.
     *
     * @ORM\Column(type="commission_statement_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var MonetaryAmount The total amount due.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty(iri="http://schema.org/totalPaymentDue")
     */
    protected $totalPaymentDue;

    public function __construct()
    {
        $this->data = new ArrayCollection();
        $this->totalPaymentDue = new MonetaryAmount();
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
     * Sets bankAccountNumber.
     *
     * @param string $bankAccountNumber|null
     *
     * @return $this
     */
    public function setBankAccountNumber(?string $bankAccountNumber)
    {
        $this->bankAccountNumber = $bankAccountNumber;

        return $this;
    }

    /**
     * Gets bankAccountNumber.
     *
     * @return string|null
     */
    public function getBankAccountNumber(): ?string
    {
        return $this->bankAccountNumber;
    }

    /**
     * Sets bankName.
     *
     * @param string $bankName|null
     *
     * @return $this
     */
    public function setBankName(?string $bankName)
    {
        $this->bankName = $bankName;

        return $this;
    }

    /**
     * Gets bankName.
     *
     * @return string|null
     */
    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    /**
     * Sets confirmationNumber.
     *
     * @param string $confirmationNumber|null
     *
     * @return $this
     */
    public function setConfirmationNumber(?string $confirmationNumber)
    {
        $this->confirmationNumber = $confirmationNumber;

        return $this;
    }

    /**
     * Gets confirmationNumber.
     *
     * @return string|null
     */
    public function getConfirmationNumber(): ?string
    {
        return $this->confirmationNumber;
    }

    /**
     * Adds datum.
     *
     * @param PartnerCommissionStatementData $datum
     *
     * @return $this
     */
    public function addData(PartnerCommissionStatementData $datum)
    {
        $this->data[] = $datum;

        return $this;
    }

    /**
     * Removes datum.
     *
     * @param PartnerCommissionStatementData $datum
     *
     * @return $this
     */
    public function removeData(PartnerCommissionStatementData $datum)
    {
        if (false !== ($key = \array_search($datum, $this->data, true))) {
            \array_splice($this->data, $key, 1);
        }

        return $this;
    }

    /**
     * Gets data.
     *
     * @return PartnerCommissionStatementData[]
     */
    public function getData(): array
    {
        return $this->data->getValues();
    }

    /**
     * Sets endDate.
     *
     * @param \DateTime $endDate
     *
     * @return $this
     */
    public function setEndDate(\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Gets endDate.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
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
     * Sets partner.
     *
     * @param Partner $partner
     *
     * @return $this
     */
    public function setPartner(Partner $partner)
    {
        $this->partner = $partner;

        return $this;
    }

    /**
     * Gets partner.
     *
     * @return Partner
     */
    public function getPartner(): Partner
    {
        return $this->partner;
    }

    /**
     * Sets paymentMethod.
     *
     * @param string $paymentMethod|null
     *
     * @return $this
     */
    public function setPaymentMethod(?string $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Gets paymentMethod.
     *
     * @return string|null
     */
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * Sets paymentReceipt.
     *
     * @param DigitalDocument|null $paymentReceipt
     *
     * @return $this
     */
    public function setPaymentReceipt(?DigitalDocument $paymentReceipt)
    {
        $this->paymentReceipt = $paymentReceipt;

        return $this;
    }

    /**
     * Gets paymentReceipt.
     *
     * @return DigitalDocument|null
     */
    public function getPaymentReceipt(): ?DigitalDocument
    {
        return $this->paymentReceipt;
    }

    /**
     * Sets paymentStatus.
     *
     * @param PaymentStatus $paymentStatus
     *
     * @return $this
     */
    public function setPaymentStatus(PaymentStatus $paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    /**
     * Gets paymentStatus.
     *
     * @return PaymentStatus
     */
    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
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
     * Sets statementNumber.
     *
     * @param string $statementNumber
     *
     * @return $this
     */
    public function setStatementNumber(string $statementNumber)
    {
        $this->statementNumber = $statementNumber;

        return $this;
    }

    /**
     * Gets statementNumber.
     *
     * @return string
     */
    public function getStatementNumber(): string
    {
        return $this->statementNumber;
    }

    /**
     * Sets status.
     *
     * @param CommissionStatementStatus $status
     *
     * @return $this
     */
    public function setStatus(CommissionStatementStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return CommissionStatementStatus
     */
    public function getStatus(): CommissionStatementStatus
    {
        return $this->status;
    }

    /**
     * Sets totalPaymentDue.
     *
     * @param MonetaryAmount $totalPaymentDue
     *
     * @return $this
     */
    public function setTotalPaymentDue(MonetaryAmount $totalPaymentDue)
    {
        $this->totalPaymentDue = $totalPaymentDue;

        return $this;
    }

    /**
     * Gets totalPaymentDue.
     *
     * @return MonetaryAmount
     */
    public function getTotalPaymentDue(): MonetaryAmount
    {
        return $this->totalPaymentDue;
    }
}
