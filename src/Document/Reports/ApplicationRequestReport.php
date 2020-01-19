<?php

declare(strict_types=1);

namespace App\Document\Reports;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="application_requests")
 * @ODM\Indexes({
 *   @ODM\Index(keys={"dateCreated"="asc"})
 * })
 */
class ApplicationRequestReport
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string|null the ID of this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $applicationRequestId;

    /**
     * @var string|null Average consumption
     *
     * @ODM\Field(type="string")
     */
    protected $averageConsumption;

    /**
     * @var string|null the type of this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $type;

    /**
     * @var string|null the contract associated with this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $contract;

    /**
     * @var string|null the type of contract associated with this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $contractType;

    /**
     * @var string|null the type of residential building this Application Request is for
     *
     * @ODM\Field(type="string")
     */
    protected $premiseType;

    /**
     * @var string|null the type of commercial building this Application Request is for
     *
     * @ODM\Field(type="string")
     */
    protected $industry;

    /**
     * @var string|null the code of the tariff rate selected for this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $tariffRateCode;

    /**
     * @var string|null the tariff rate selected for this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $tariffRate;

    /**
     * @var string|null the referral code used when making this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $referralCode;

    /**
     * @var string|null the meter option chosen when making this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $meterOption;

    /**
     * @var string|null the SP Account number supplied when making this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $spAccountNumber;

    /**
     * @var \DateTime|null When the customer prefers to activate their contract
     *
     * @ODM\Field(type="date")
     */
    protected $preferredStartDate;

    /**
     * @var \DateTime|null When the customer prefers to deactivate their contract
     *
     * @ODM\Field(type="date")
     */
    protected $preferredEndDate;

    /**
     * @var string|null specifies if the self read option allowed for this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $selfReadOption;

    /**
     * @var string|null specifies if the this Application Request is a GIRO Application
     *
     * @ODM\Field(type="string")
     */
    protected $giroApplication;

    /**
     * @var string|null where this Application Request was created from
     *
     * @ODM\Field(type="string")
     */
    protected $source;

    /**
     * @var string|null specifies deposit refund type for this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $deposit;

    /**
     * @var CustomerDetails|null
     *
     * @ODM\EmbedOne(targetDocument="CustomerDetails")
     */
    protected $customerDetails;

    /**
     * @var string|null the identification number of the corporate customer of this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $corporateIdentificationNumber;

    /**
     * @var string|null name of the company making this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $companyName;

    /**
     * @var CustomerDetails|null
     *
     * @ODM\EmbedOne(targetDocument="CustomerDetails")
     */
    protected $contactPersonDetails;

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
     * @var Address|null
     *
     * @ODM\EmbedOne(targetDocument="Address")
     */
    protected $refundAddressDetails;

    /**
     * @var string|null remarks on this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $remarks;

    /**
     * @var string|null status of this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $status;

    /**
     * @var string|null reason why the customer wants to terminate this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $terminationReason;

    /**
     * @var string|null referral source the customer chooses for this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $referralSource;

    /**
     * @var string|null referral source the customer indicates for this Application Request
     *
     * @ODM\Field(type="string")
     */
    protected $indicate;

    /**
     * @var string|null specifies if this Application Request was created by the customer themselves
     *
     * @ODM\Field(type="string")
     */
    protected $selfApplication;

    /**
     * @var string|null specifies if the customer of this Application Request subscribed for E-billing
     *
     * @ODM\Field(type="string")
     */
    protected $eBilling;

    /**
     * @var string|null specifies the partner that created this application request
     *
     * @ODM\Field(type="string")
     */
    protected $agency;

    /**
     * @var string|null specifies the sales rep that created this application request
     *
     * @ODM\Field(type="string")
     */
    protected $salesRep;

    /**
     * @var string|null account number of the partner that created this application request
     *
     * @ODM\Field(type="string")
     */
    protected $partnerCode;

    /**
     * @var string|null channel used to create this application request
     *
     * @ODM\Field(type="string")
     */
    protected $channel;

    /**
     * @var string|null location code the customer entered for this application request
     *
     * @ODM\Field(type="string")
     */
    protected $locationCode;

    /**
     * @var string|null payment mode the customer chose for this application request
     *
     * @ODM\Field(type="string")
     */
    protected $paymentMode;

    /**
     * @var \DateTime|null When the Application Request is to be renewed
     *
     * @ODM\Field(type="date")
     */
    protected $renewalStartDate;

    /**
     * @var \DateTime|null lock in date for this application request
     *
     * @ODM\Field(type="date")
     */
    protected $lockInDate;

    /**
     * @var \DateTime|null When the Application Request was created
     *
     * @ODM\Field(type="date")
     */
    protected $dateCreated;

    /**
     * @var \DateTime|null When the Application Request was modified
     *
     * @ODM\Field(type="date")
     */
    protected $dateModified;

    /**
     * @var string|null promotion code the customer used for this application request
     *
     * @ODM\Field(type="string")
     */
    protected $promotionCode;

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
     * @return string|null
     */
    public function getApplicationRequestId(): ?string
    {
        return $this->applicationRequestId;
    }

    /**
     * @param string|null $applicationRequestId
     */
    public function setApplicationRequestId(?string $applicationRequestId): void
    {
        $this->applicationRequestId = $applicationRequestId;
    }

    /**
     * @return string|null
     */
    public function getAverageConsumption(): ?string
    {
        return $this->averageConsumption;
    }

    /**
     * @param string|null $averageConsumption
     */
    public function setAverageConsumption(?string $averageConsumption): void
    {
        $this->averageConsumption = $averageConsumption;
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
     * @return string|null
     */
    public function getContract(): ?string
    {
        return $this->contract;
    }

    /**
     * @param string|null $contract
     */
    public function setContract(?string $contract): void
    {
        $this->contract = $contract;
    }

    /**
     * @return string|null
     */
    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    /**
     * @param string|null $contractType
     */
    public function setContractType(?string $contractType): void
    {
        $this->contractType = $contractType;
    }

    /**
     * @return string|null
     */
    public function getPremiseType(): ?string
    {
        return $this->premiseType;
    }

    /**
     * @param string|null $premiseType
     */
    public function setPremiseType(?string $premiseType): void
    {
        $this->premiseType = $premiseType;
    }

    /**
     * @return string|null
     */
    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    /**
     * @param string|null $industry
     */
    public function setIndustry(?string $industry): void
    {
        $this->industry = $industry;
    }

    /**
     * @return string|null
     */
    public function getTariffRateCode(): ?string
    {
        return $this->tariffRateCode;
    }

    /**
     * @param string|null $tariffRateCode
     */
    public function setTariffRateCode(?string $tariffRateCode): void
    {
        $this->tariffRateCode = $tariffRateCode;
    }

    /**
     * @return string|null
     */
    public function getTariffRate(): ?string
    {
        return $this->tariffRate;
    }

    /**
     * @param string|null $tariffRate
     */
    public function setTariffRate(?string $tariffRate): void
    {
        $this->tariffRate = $tariffRate;
    }

    /**
     * @return string|null
     */
    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }

    /**
     * @param string|null $referralCode
     */
    public function setReferralCode(?string $referralCode): void
    {
        $this->referralCode = $referralCode;
    }

    /**
     * @return string|null
     */
    public function getMeterOption(): ?string
    {
        return $this->meterOption;
    }

    /**
     * @param string|null $meterOption
     */
    public function setMeterOption(?string $meterOption): void
    {
        $this->meterOption = $meterOption;
    }

    /**
     * @return string|null
     */
    public function getSpAccountNumber(): ?string
    {
        return $this->spAccountNumber;
    }

    /**
     * @param string|null $spAccountNumber
     */
    public function setSpAccountNumber(?string $spAccountNumber): void
    {
        $this->spAccountNumber = $spAccountNumber;
    }

    /**
     * @return \DateTime|null
     */
    public function getPreferredStartDate(): ?\DateTime
    {
        return $this->preferredStartDate;
    }

    /**
     * @param \DateTime|null $preferredStartDate
     */
    public function setPreferredStartDate(?\DateTime $preferredStartDate): void
    {
        $this->preferredStartDate = $preferredStartDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getPreferredEndDate(): ?\DateTime
    {
        return $this->preferredEndDate;
    }

    /**
     * @param \DateTime|null $preferredEndDate
     */
    public function setPreferredEndDate(?\DateTime $preferredEndDate): void
    {
        $this->preferredEndDate = $preferredEndDate;
    }

    /**
     * @return string|null
     */
    public function getSelfReadOption(): ?string
    {
        return $this->selfReadOption;
    }

    /**
     * @param string|null $selfReadOption
     */
    public function setSelfReadOption(?string $selfReadOption): void
    {
        $this->selfReadOption = $selfReadOption;
    }

    /**
     * @return string|null
     */
    public function getGiroApplication(): ?string
    {
        return $this->giroApplication;
    }

    /**
     * @param string|null $giroApplication
     */
    public function setGiroApplication(?string $giroApplication): void
    {
        $this->giroApplication = $giroApplication;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @param string|null $source
     */
    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    /**
     * @return string|null
     */
    public function getDeposit(): ?string
    {
        return $this->deposit;
    }

    /**
     * @param string|null $deposit
     */
    public function setDeposit(?string $deposit): void
    {
        $this->deposit = $deposit;
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
    public function getCorporateIdentificationNumber(): ?string
    {
        return $this->corporateIdentificationNumber;
    }

    /**
     * @param string|null $corporateIdentificationNumber
     */
    public function setCorporateIdentificationNumber(?string $corporateIdentificationNumber): void
    {
        $this->corporateIdentificationNumber = $corporateIdentificationNumber;
    }

    /**
     * @return string|null
     */
    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    /**
     * @param string|null $companyName
     */
    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * @return CustomerDetails|null
     */
    public function getContactPersonDetails(): ?CustomerDetails
    {
        return $this->contactPersonDetails;
    }

    /**
     * @param CustomerDetails|null $contactPersonDetails
     */
    public function setContactPersonDetails(?CustomerDetails $contactPersonDetails): void
    {
        $this->contactPersonDetails = $contactPersonDetails;
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
     * @return Address|null
     */
    public function getRefundAddressDetails(): ?Address
    {
        return $this->refundAddressDetails;
    }

    /**
     * @param Address|null $refundAddressDetails
     */
    public function setRefundAddressDetails(?Address $refundAddressDetails): void
    {
        $this->refundAddressDetails = $refundAddressDetails;
    }

    /**
     * @return string|null
     */
    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    /**
     * @param string|null $remarks
     */
    public function setRemarks(?string $remarks): void
    {
        $this->remarks = $remarks;
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
    public function getTerminationReason(): ?string
    {
        return $this->terminationReason;
    }

    /**
     * @param string|null $terminationReason
     */
    public function setTerminationReason(?string $terminationReason): void
    {
        $this->terminationReason = $terminationReason;
    }

    /**
     * @return string|null
     */
    public function getReferralSource(): ?string
    {
        return $this->referralSource;
    }

    /**
     * @param string|null $referralSource
     */
    public function setReferralSource(?string $referralSource): void
    {
        $this->referralSource = $referralSource;
    }

    /**
     * @return string|null
     */
    public function getIndicate(): ?string
    {
        return $this->indicate;
    }

    /**
     * @param string|null $indicate
     */
    public function setIndicate(?string $indicate): void
    {
        $this->indicate = $indicate;
    }

    /**
     * @return string|null
     */
    public function getSelfApplication(): ?string
    {
        return $this->selfApplication;
    }

    /**
     * @param string|null $selfApplication
     */
    public function setSelfApplication(?string $selfApplication): void
    {
        $this->selfApplication = $selfApplication;
    }

    /**
     * @return string|null
     */
    public function getEBilling(): ?string
    {
        return $this->eBilling;
    }

    /**
     * @param string|null $eBilling
     */
    public function setEBilling(?string $eBilling): void
    {
        $this->eBilling = $eBilling;
    }

    /**
     * @return string|null
     */
    public function getAgency(): ?string
    {
        return $this->agency;
    }

    /**
     * @param string|null $agency
     */
    public function setAgency(?string $agency): void
    {
        $this->agency = $agency;
    }

    /**
     * @return string|null
     */
    public function getSalesRep(): ?string
    {
        return $this->salesRep;
    }

    /**
     * @param string|null $salesRep
     */
    public function setSalesRep(?string $salesRep): void
    {
        $this->salesRep = $salesRep;
    }

    /**
     * @return string|null
     */
    public function getPartnerCode(): ?string
    {
        return $this->partnerCode;
    }

    /**
     * @param string|null $partnerCode
     */
    public function setPartnerCode(?string $partnerCode): void
    {
        $this->partnerCode = $partnerCode;
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
     * @return string|null
     */
    public function getLocationCode(): ?string
    {
        return $this->locationCode;
    }

    /**
     * @param string|null $locationCode
     */
    public function setLocationCode(?string $locationCode): void
    {
        $this->locationCode = $locationCode;
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
     */
    public function setPaymentMode(?string $paymentMode): void
    {
        $this->paymentMode = $paymentMode;
    }

    /**
     * @return \DateTime|null
     */
    public function getRenewalStartDate(): ?\DateTime
    {
        return $this->renewalStartDate;
    }

    /**
     * @param \DateTime|null $renewalStartDate
     */
    public function setRenewalStartDate(?\DateTime $renewalStartDate): void
    {
        $this->renewalStartDate = $renewalStartDate;
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
}
