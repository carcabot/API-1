<?php

declare(strict_types=1);

namespace App\Document\Reports;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="contracts")
 * @ODM\Indexes({
 *   @ODM\Index(keys={"dateCreated"="asc"})
 * })
 */
class ContractReport
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var CustomerDetails|null
     *
     * @ODM\EmbedOne(targetDocument="CustomerDetails")
     */
    protected $customerDetails;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $contractNumber;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $status;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $type;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    protected $startDate;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    protected $endDate;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    protected $lockInDate;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $meterType;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $msslNumber;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $ebsNumber;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $promotionCode;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $promotionName;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $category;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected $paymentMethod;

    /**
     * @var Address|null
     *
     * @ODM\EmbedOne(targetDocument="Address")
     */
    protected $premiseAddressDetails;

    /**
     * @var Address|null
     *
     * @ODM\EmbedOne(targetDocument="Address")
     */
    protected $mailingAddressDetails;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    protected $dateCreated;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    protected $dateModified;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return CustomerDetails|null
     */
    public function getCustomerDetails(): ?CustomerDetails
    {
        return $this->customerDetails;
    }

    /**
     * @param CustomerDetails|null $customerDetails
     */
    public function setCustomerDetails(?CustomerDetails $customerDetails): void
    {
        $this->customerDetails = $customerDetails;
    }

    /**
     * @return string|null
     */
    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    /**
     * @param string|null $contractNumber
     */
    public function setContractNumber(?string $contractNumber): void
    {
        $this->contractNumber = $contractNumber;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime|null $startDate
     */
    public function setStartDate(?\DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime|null $endDate
     */
    public function setEndDate(?\DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getLockInDate(): ?\DateTime
    {
        return $this->lockInDate;
    }

    /**
     * @param \DateTime|null $lockInDate
     */
    public function setLockInDate(?\DateTime $lockInDate): void
    {
        $this->lockInDate = $lockInDate;
    }

    /**
     * @return string|null
     */
    public function getMeterType(): ?string
    {
        return $this->meterType;
    }

    /**
     * @param string|null $meterType
     */
    public function setMeterType(?string $meterType): void
    {
        $this->meterType = $meterType;
    }

    /**
     * @return string|null
     */
    public function getMsslNumber(): ?string
    {
        return $this->msslNumber;
    }

    /**
     * @param string|null $msslNumber
     */
    public function setMsslNumber(?string $msslNumber): void
    {
        $this->msslNumber = $msslNumber;
    }

    /**
     * @return string|null
     */
    public function getEbsNumber(): ?string
    {
        return $this->ebsNumber;
    }

    /**
     * @param string|null $ebsNumber
     */
    public function setEbsNumber(?string $ebsNumber): void
    {
        $this->ebsNumber = $ebsNumber;
    }

    /**
     * @return string|null
     */
    public function getPromotionCode(): ?string
    {
        return $this->promotionCode;
    }

    /**
     * @param string|null $promotionCode
     */
    public function setPromotionCode(?string $promotionCode): void
    {
        $this->promotionCode = $promotionCode;
    }

    /**
     * @return string|null
     */
    public function getPromotionName(): ?string
    {
        return $this->promotionName;
    }

    /**
     * @param string|null $promotionName
     */
    public function setPromotionName(?string $promotionName): void
    {
        $this->promotionName = $promotionName;
    }

    /**
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @param string|null $category
     */
    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    /**
     * @return string|null
     */
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string|null $paymentMethod
     */
    public function setPaymentMethod(?string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return Address|null
     */
    public function getPremiseAddressDetails(): ?Address
    {
        return $this->premiseAddressDetails;
    }

    /**
     * @param Address|null $premiseAddressDetails
     */
    public function setPremiseAddressDetails(?Address $premiseAddressDetails): void
    {
        $this->premiseAddressDetails = $premiseAddressDetails;
    }

    /**
     * @return Address|null
     */
    public function getMailingAddressDetails(): ?Address
    {
        return $this->mailingAddressDetails;
    }

    /**
     * @param Address|null $mailingAddressDetails
     */
    public function setMailingAddressDetails(?Address $mailingAddressDetails): void
    {
        $this->mailingAddressDetails = $mailingAddressDetails;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param \DateTime|null $dateCreated
     */
    public function setDateCreated(?\DateTime $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateModified(): ?\DateTime
    {
        return $this->dateModified;
    }

    /**
     * @param \DateTime|null $dateModified
     */
    public function setDateModified(?\DateTime $dateModified): void
    {
        $this->dateModified = $dateModified;
    }
}
