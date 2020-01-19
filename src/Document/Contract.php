<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="contracts")
 */
class Contract
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string|null The application request number.
     *
     * @ODM\Field(type="string", name="_applicationId")
     */
    protected $applicationRequestNumber;

    /**
     * @var string[]|null The attachments.
     *
     * @ODM\Field(type="collection", name="attachments")
     */
    protected $attachments;

    /**
     * @var string|null The billing period number.
     *
     * @ODM\Field(type="string", name="billing_period_id")
     */
    protected $billingPeriodId;

    /**
     * @var int|null The consumption amount.
     *
     * @ODM\Field(type="int", name="consumption_amount")
     */
    protected $consumptionAmount;

    /**
     * @var int|null The contract closure notice day.
     *
     * @ODM\Field(type="int", name="contract_closure_notice_day")
     */
    protected $contractClosureNoticeDay;

    /**
     * @var bool|null The contract customize.
     *
     * @ODM\Field(type="boolean", name="contract_customize")
     */
    protected $contractCustomize;

    /**
     * @var \DateTime|null The contract end date.
     *
     * @ODM\Field(type="date", name="contract_end_date")
     */
    protected $contractEndDate;

    /**
     * @var \DateTime|null The contract expire date.
     *
     * @ODM\Field(type="date", name="contract_expire_date")
     */
    protected $contractExpireDate;

    /**
     * @var string|null The contract number.
     *
     * @ODM\Field(type="string", name="_contractId")
     */
    protected $contractId;

    /**
     * @var \DateTime|null The contract start date.
     *
     * @ODM\Field(type="date", name="contract_start_date")
     */
    protected $contractStartDate;

    /**
     * @var string|null The customer account.
     *
     * @ODM\Field(type="string", name="customer_account")
     */
    protected $customerAccount;

    /**
     * @var string|null The ebs account number.
     *
     * @ODM\Field(type="string", name="ebs_account_no")
     */
    protected $ebsAccountNo;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="indicate")
     */
    protected $indicate;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="location_code")
     */
    protected $locationCode;

    /**
     * @var \DateTime|null The lock in period.
     *
     * @ODM\Field(type="date", name="lock_in_period")
     */
    protected $lockInPeriod;

    /**
     * @var string|null The meter option.
     *
     * @ODM\Field(type="string", name="meter_option")
     */
    protected $meterOption;

    /**
     * @var string|null The mssl account number.
     *
     * @ODM\Field(type="string", name="mssl_account_no")
     */
    protected $msslAccountNo;

    /**
     * @var string|null The nick name.
     *
     * @ODM\Field(type="string", name="nickname")
     */
    protected $nickName;

    /**
     * @var Partner|null The partner data.
     *
     * @ODM\EmbedOne(targetDocument="Partner", name="partner")
     */
    protected $partner;

    /**
     * @var string|null The prefer contact person.
     *
     * @ODM\Field(type="string", name="prefer_contact_person")
     */
    protected $preferContactPerson;

    /**
     * @var \DateTime|null The prefer turn off date.
     *
     * @ODM\Field(type="date", name="prefer_turn_off_date")
     */
    protected $preferTurnOffDate;

    /**
     * @var \DateTime|null The prefer turn on date.
     *
     * @ODM\Field(type="date", name="prefer_turn_on_date")
     */
    protected $preferTurnOnDate;

    /**
     * @var string[]|null The activity.
     *
     * @ODM\Field(type="collection", name="address")
     */
    protected $addresses;

    /**
     * @var string|null The reference source of the application/contract.
     *
     * @ODM\Field(type="string", name="ref_source")
     */
    protected $refSource;

    /**
     * @var string|null The reference source of the application/contract.
     *
     * @ODM\Field(type="string", name="referralCode")
     */
    protected $referralCode;

    /**
     * @var string|null The refund payee name.
     *
     * @ODM\Field(type="string", name="refund_payee_name")
     */
    protected $refundPayeeName;

    /**
     * @var string|null The refund payee nric.
     *
     * @ODM\Field(type="string", name="refund_payee_nric")
     */
    protected $refundPayeeNric;

    /**
     * @var string|null The comment on the application/contract.
     *
     * @ODM\Field(type="string", name="remark")
     */
    protected $remark;

    /**
     * @var \DateTime|null The renewal start date.
     *
     * @ODM\Field(type="date", name="renewal_start_date")
     */
    protected $renewalStartDate;

    /**
     * @var bool|null The self read option.
     *
     * @ODM\Field(type="boolean", name="self_read_option")
     */
    protected $selfReadOption;

    /**
     * @var bool|null The giro option.
     *
     * @ODM\Field(type="boolean", name="giro_option")
     */
    protected $giroOption;

    /**
     * @var bool|null
     *
     * @ODM\Field(type="boolean", name="is_owner")
     */
    protected $isOwner;

    /**
     * @var bool|null
     *
     * @ODM\Field(type="boolean", name="as_premise_address")
     */
    protected $asPremiseAddress;

    /**
     * @var bool|null
     *
     * @ODM\Field(type="boolean", name="is_diff_payee")
     */
    protected $isDiffPayee;

    /**
     * @var bool|null
     *
     * @ODM\Field(type="boolean", name="has_mssl")
     */
    protected $hasMssl;

    /**
     * @var bool|null
     *
     * @ODM\Field(type="boolean", name="is_spaccount_holder")
     */
    protected $isSpAccountHolder;

    /**
     * @var bool|null
     *
     * @ODM\Field(type="boolean", name="is_billedon_paper")
     */
    protected $isBillEdonPaper;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="promotion_code_id")
     */
    protected $promotionCodeId;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="partner_id")
     */
    protected $partnerId;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="customer_id")
     */
    protected $customerId;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="sale_rep_id")
     */
    protected $saleRepId;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="contact_id")
     */
    protected $contactId;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="average_consumption")
     */
    protected $averageConsumption;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="source")
     */
    protected $source;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="status")
     */
    protected $status;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="contract_status")
     */
    protected $contractStatus;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="type")
     */
    protected $type;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="sub_type")
     */
    protected $subType;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="app_type")
     */
    protected $appType;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="contract_period")
     */
    protected $contractPeriod;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="deposit")
     */
    protected $deposit;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="deposit_amount")
     */
    protected $depositAmount;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="deposit_currency")
     */
    protected $depositCurrency;

    /**
     * @var string|null The temporary id.
     *
     * @ODM\Field(type="string", name="_tempId")
     */
    protected $tempId;

    /**
     * @var string|null The termination reason.
     *
     * @ODM\Field(type="string", name="termination_reason")
     */
    protected $terminationReason;

    /**
     * @var \DateTime|null The tariff created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="_createdBy")
     */
    protected $createdBy;

    /**
     * @var \DateTime|null The tariff created at
     *
     * @ODM\Field(type="date", name="_updatedAt")
     */
    protected $updatedAt;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="_updatedBy")
     */
    protected $updatedBy;

    /**
     * Gets id.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string[]|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    /**
     * Gets applicationRequestNumber.
     *
     * @return string|null
     */
    public function getApplicationRequestNumber(): ?string
    {
        return $this->applicationRequestNumber;
    }

    /**
     * @return array|null
     */
    public function getAddresses(): ?array
    {
        return $this->addresses;
    }

    /**
     * Get contractId.
     *
     * @return string|null
     */
    public function getContractId()
    {
        return $this->contractId;
    }

    /**
     * Gets tempId.
     *
     * @return string|null
     */
    public function getTempId(): ?string
    {
        return $this->tempId;
    }

    /**
     * @return string|null
     */
    public function getBillingPeriodId()
    {
        return $this->billingPeriodId;
    }

    /**
     * @return int|null
     */
    public function getConsumptionAmount()
    {
        return $this->consumptionAmount;
    }

    /**
     * @return int|null
     */
    public function getContractClosureNoticeDay()
    {
        return $this->contractClosureNoticeDay;
    }

    /**
     * @return bool|null
     */
    public function getContractCustomize()
    {
        return $this->contractCustomize;
    }

    /**
     * @return \DateTime|null
     */
    public function getContractEndDate()
    {
        return $this->contractEndDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getContractExpireDate()
    {
        return $this->contractExpireDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getContractStartDate()
    {
        return $this->contractStartDate;
    }

    /**
     * @return string|null
     */
    public function getCustomerAccount()
    {
        return $this->customerAccount;
    }

    /**
     * @return string|null
     */
    public function getEbsAccountNo()
    {
        return $this->ebsAccountNo;
    }

    /**
     * @return string|null
     */
    public function getIndicate()
    {
        return $this->indicate;
    }

    /**
     * @return string|null
     */
    public function getLocationCode()
    {
        return $this->locationCode;
    }

    /**
     * @return \DateTime|null
     */
    public function getLockInPeriod()
    {
        return $this->lockInPeriod;
    }

    /**
     * @return string|null
     */
    public function getMeterOption()
    {
        return $this->meterOption;
    }

    /**
     * @return string|null
     */
    public function getMsslAccountNo()
    {
        return $this->msslAccountNo;
    }

    /**
     * @return string|null
     */
    public function getNickName()
    {
        return $this->nickName;
    }

    /**
     * @return Partner|null
     */
    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    /**
     * @return string|null
     */
    public function getPreferContactPerson()
    {
        return $this->preferContactPerson;
    }

    /**
     * @return \DateTime|null
     */
    public function getPreferTurnOffDate()
    {
        return $this->preferTurnOffDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getPreferTurnOnDate()
    {
        return $this->preferTurnOnDate;
    }

    /**
     * @return string|null
     */
    public function getRefSource()
    {
        return $this->refSource;
    }

    /**
     * @return string|null
     */
    public function getRefundPayeeName()
    {
        return $this->refundPayeeName;
    }

    /**
     * @return string|null
     */
    public function getRefundPayeeNric()
    {
        return $this->refundPayeeNric;
    }

    /**
     * @return string|null
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @return \DateTime|null
     */
    public function getRenewalStartDate()
    {
        return $this->renewalStartDate;
    }

    /**
     * @return bool|null
     */
    public function getSelfReadOption()
    {
        return $this->selfReadOption;
    }

    /**
     * @return bool|null
     */
    public function getGiroOption()
    {
        return $this->giroOption;
    }

    /**
     * @return bool|null
     */
    public function getIsOwner()
    {
        return $this->isOwner;
    }

    /**
     * @return bool|null
     */
    public function getAsPremiseAddress()
    {
        return $this->asPremiseAddress;
    }

    /**
     * @return bool|null
     */
    public function getIsDiffPayee()
    {
        return $this->isDiffPayee;
    }

    /**
     * @return bool|null
     */
    public function getHasMssl()
    {
        return $this->hasMssl;
    }

    /**
     * @return bool|null
     */
    public function getIsSpAccountHolder()
    {
        return $this->isSpAccountHolder;
    }

    /**
     * @return bool|null
     */
    public function getIsBillEdonPaper()
    {
        return $this->isBillEdonPaper;
    }

    /**
     * @return string|null
     */
    public function getPromotionCodeId()
    {
        return $this->promotionCodeId;
    }

    /**
     * @return string|null
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @return string|null
     */
    public function getSaleRepId()
    {
        return $this->saleRepId;
    }

    /**
     * @return string|null
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * @return string|null
     */
    public function getAverageConsumption()
    {
        return $this->averageConsumption;
    }

    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getContractStatus()
    {
        return $this->contractStatus;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getSubType()
    {
        return $this->subType;
    }

    /**
     * @return string|null
     */
    public function getAppType()
    {
        return $this->appType;
    }

    /**
     * @return string|null
     */
    public function getContractPeriod()
    {
        return $this->contractPeriod;
    }

    /**
     * @return string|null
     */
    public function getDeposit()
    {
        return $this->deposit;
    }

    /**
     * @return string|null
     */
    public function getDepositAmount()
    {
        return $this->depositAmount;
    }

    /**
     * @return string|null
     */
    public function getDepositCurrency()
    {
        return $this->depositCurrency;
    }

    /**
     * @return string|null
     */
    public function getTerminationReason()
    {
        return $this->terminationReason;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return string|null
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return string|null
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @return string|null
     */
    public function getReferralCode()
    {
        return $this->referralCode;
    }
}
