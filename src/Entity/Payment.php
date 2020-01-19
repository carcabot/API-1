<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\PaymentStatus;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

/**
 * A payment.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"payment_read"}},
 *     "denormalization_context"={"groups"={"payment_write"}},
 * })
 */
class Payment
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
     * @var MonetaryAmount The amount of money.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty(iri="http://schema.org/amount")
     */
    protected $amount;

    /**
     * @var string|null The bank account number.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bankAccountHolderName;

    /**
     * @var string|null The bank account number.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bankAccountNumber;

    /**
     * @var string|null The bank code.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bankCode;

    /**
     * @var string|null The bank name.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $bankName;

    /**
     * @var string|null A number that confirms the given order or payment has been received.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/confirmationNumber")
     */
    protected $confirmationNumber;

    /**
     * @var PhoneNumber|null The contact number.
     *
     * @ORM\Column(type="phone_number", nullable=true)
     * @ApiProperty()
     */
    protected $contactNumber;

    /**
     * @var string|null Email address.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty(iri="http://schema.org/email")
     */
    protected $email;

    /**
     * @var string|null The invoice number.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty()
     */
    protected $invoiceNumber;

    /**
     * @var string|null The name of the credit card or other method of payment for the order.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/paymentMethod")
     */
    protected $paymentMethod;

    /**
     * @var string The payment number.
     *
     * @ORM\Column(type="string", length=128, unique=true, nullable=false)
     * @ApiProperty()
     */
    protected $paymentNumber;

    /**
     * @var DigitalDocument|null A softcopy of the receipt for the payment made as proof of payment.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $paymentReceipt;

    /**
     * @var string|null The URL for sending a payment.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/paymentUrl")
     */
    protected $paymentUrl;

    /**
     * @var string|null The return message, reason of failure or success message.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $returnMessage;

    /**
     * @var PaymentStatus The status of payment; whether the invoice has been paid or not.
     *
     * @ORM\Column(type="payment_status_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/paymentStatus")
     */
    protected $status;

    public function __construct()
    {
        $this->amount = new MonetaryAmount();
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
     * Sets amount.
     *
     * @param MonetaryAmount $amount
     *
     * @return $this
     */
    public function setAmount(MonetaryAmount $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Gets amount.
     *
     * @return MonetaryAmount
     */
    public function getAmount(): MonetaryAmount
    {
        return $this->amount;
    }

    /**
     * Sets bankAccountHolderName.
     *
     * @param string $bankAccountHolderName|null
     *
     * @return $this
     */
    public function setBankAccountHolderName(?string $bankAccountHolderName)
    {
        $this->bankAccountHolderName = $bankAccountHolderName;

        return $this;
    }

    /**
     * Gets bankAccountHolderName.
     *
     * @return string|null
     */
    public function getBankAccountHolderName(): ?string
    {
        return $this->bankAccountHolderName;
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
     * Sets bankCode.
     *
     * @param string $bankCode|null
     *
     * @return $this
     */
    public function setBankCode(?string $bankCode)
    {
        $this->bankCode = $bankCode;

        return $this;
    }

    /**
     * Gets bankCode.
     *
     * @return string|null
     */
    public function getBankCode(): ?string
    {
        return $this->bankCode;
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
     * Sets contactNumber.
     *
     * @param PhoneNumber|null $contactNumber
     *
     * @return $this
     */
    public function setContactNumber(?PhoneNumber $contactNumber)
    {
        $this->contactNumber = $contactNumber;

        return $this;
    }

    /**
     * Gets contactNumber.
     *
     * @return PhoneNumber|null
     */
    public function getContactNumber(): ?PhoneNumber
    {
        return $this->contactNumber;
    }

    /**
     * Sets email.
     *
     * @param string $email|null
     *
     * @return $this
     */
    public function setEmail(?string $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Gets email.
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Sets invoiceNumber.
     *
     * @param string $invoiceNumber|null
     *
     * @return $this
     */
    public function setInvoiceNumber(?string $invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    /**
     * Gets invoiceNumber.
     *
     * @return string|null
     */
    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
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
     * Sets paymentNumber.
     *
     * @param string $paymentNumber
     *
     * @return $this
     */
    public function setPaymentNumber(string $paymentNumber)
    {
        $this->paymentNumber = $paymentNumber;

        return $this;
    }

    /**
     * Gets paymentNumber.
     *
     * @return string
     */
    public function getPaymentNumber(): string
    {
        return $this->paymentNumber;
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
     * Sets paymentUrl.
     *
     * @param string|null $paymentUrl
     *
     * @return $this
     */
    public function setPaymentUrl(?string $paymentUrl)
    {
        $this->paymentUrl = $paymentUrl;

        return $this;
    }

    /**
     * Gets paymentUrl.
     *
     * @return string|null
     */
    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    /**
     * Sets returnMessage.
     *
     * @param string $returnMessage|null
     *
     * @return $this
     */
    public function setReturnMessage(?string $returnMessage)
    {
        $this->returnMessage = $returnMessage;

        return $this;
    }

    /**
     * Gets returnMessage.
     *
     * @return string|null
     */
    public function getReturnMessage(): ?string
    {
        return $this->returnMessage;
    }

    /**
     * Sets status.
     *
     * @param PaymentStatus $status
     *
     * @return $this
     */
    public function setStatus(PaymentStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return PaymentStatus
     */
    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }
}
