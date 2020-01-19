<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="promotion_codes")
 */
class Tariff
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string|null The ami fee
     *
     * @ODM\Field(type="string", name="ami_fee")
     */
    protected $amiFee;

    /**
     * @var string[] The application for use
     *
     * @ODM\Field(type="collection", name="application_for_use")
     */
    protected $applicationForUse;

    /**
     * @var \DateTime|null The available from.
     *
     * @ODM\Field(type="date", name="available_from")
     */
    protected $availableFrom;

    /**
     * @var string|null The bundled service.
     *
     * @ODM\Field(type="string", name="bundled_service")
     */
    protected $bundledService;

    /**
     * @var string|null The charge description.
     *
     * @ODM\Field(type="string", name="charge_description")
     */
    protected $chargeDescription;

    /**
     * @var string|null The contract duration
     *
     * @ODM\Field(type="string", name="contract_duration")
     */
    protected $contractDuration;

    /**
     * @var string|null The contract renewal
     *
     * @ODM\Field(type="string", name="contract_renewal")
     */
    protected $contractRenewal;

    /**
     * @var string[] The contract type
     *
     * @ODM\Field(type="collection", name="contract_types")
     */
    protected $contractTypes;

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
     * @var string[]|null The tariff daily rate.
     *
     * @ODM\Field(type="collection", name="daily_rate")
     */
    protected $dailyRate;

    /**
     * @var string|null The early termination charge.
     *
     * @ODM\Field(type="string", name="early_termination_charge")
     */
    protected $earlyTerminationCharge;

    /**
     * @var bool|null Is daily rate.
     *
     * @ODM\Field(type="bool", name="is_daily_rate")
     */
    protected $isDailyRate;

    /**
     * @var string|null The late payment charge
     *
     * @ODM\Field(type="string", name="late_payment_charge")
     */
    protected $latePaymentCharge;

    /**
     * @var string|null The minimum contract term.
     *
     * @ODM\Field(type="string", name="min_contract_term")
     */
    protected $minContractTerm;

    /**
     * @var string|null The one time fee
     *
     * @ODM\Field(type="string", name="one_time_fee")
     */
    protected $oneTimeFee;

    /**
     * @var string|null The other fee.
     *
     * @ODM\Field(type="string", name="other_fee")
     */
    protected $otherFee;

    /**
     * @var string|null The retailer incentive
     *
     * @ODM\Field(type="string", name="retailer_incentive")
     */
    protected $retailerIncentive;

    /**
     * @var string|null The price plan name
     *
     * @ODM\Field(type="string", name="price_plan_name")
     */
    protected $pricePlanName;

    /**
     * @var string|null The non standard price plan.
     *
     * @ODM\Field(type="string", name="price_plan_non_standard")
     */
    protected $pricePlanNonStandard;

    /**
     * @var string|null The price plan standard
     *
     * @ODM\Field(type="string", name="price_plan_standard")
     */
    protected $pricePlanStandard;

    /**
     * @var string|null The price plan type
     *
     * @ODM\Field(type="string", name="price_plan_type")
     */
    protected $pricePlanType;

    /**
     * @var string|null The retailer to bill
     *
     * @ODM\Field(type="string", name="retailer_to_bill")
     */
    protected $retailerToBill;

    /**
     * @var string|null The retailer name.
     *
     * @ODM\Field(type="string", name="retailer_name")
     */
    protected $retailerName;

    /**
     * @var string|null The security deposit
     *
     * @ODM\Field(type="string", name="security_deposit")
     */
    protected $securityDeposit;

    /**
     * @var string The tariff code.
     *
     * @ODM\Field(type="string", name="promotion_code")
     */
    protected $tariffCode;

    /**
     * @var bool|null The tariff customized.
     *
     * @ODM\Field(type="bool", name="promotion_customized")
     */
    protected $tariffCustomized;

    /**
     * @var string|null The tariff description.
     *
     * @ODM\Field(type="string", name="promotion_desc")
     */
    protected $tariffDescription;

    /**
     * @var string|null The tariff end date.
     *
     * @ODM\Field(type="string", name="promotion_end_date")
     */
    protected $tariffEndDate;

    /**
     * @var bool|null The tariff internal.
     *
     * @ODM\Field(type="bool", name="promotion_internal_only")
     */
    protected $tariffInternalOnly;

    /**
     * @var int|null The tariff limit
     *
     * @ODM\Field(type="int", name="promotion_limit")
     */
    protected $tariffLimit;

    /**
     * @var string|null The tariff name.
     *
     * @ODM\Field(type="string", name="promotion_name")
     */
    protected $tariffName;

    /**
     * @var string|null The tariff rejection note
     *
     * @ODM\Field(type="string", name="promotion_rejection_notes")
     */
    protected $tariffRejectionNotes;

    /**
     * @var string|null The tariff remark
     *
     * @ODM\Field(type="string", name="promotion_remark")
     */
    protected $tariffRemark;

    /**
     * @var string|null The tariff start date.
     *
     * @ODM\Field(type="string", name="promotion_start_date")
     */
    protected $tariffStartDate;

    /**
     * @var string The tariff status.
     *
     * @ODM\Field(type="string", name="promotion_status")
     */
    protected $tariffStatus;

    /**
     * @ODM\EmbedOne(
     * discriminatorField="type",
     * targetDocument="ToDesign")
     */
    protected $to_design;

    /**
     * @var \DateTime|null The tariff updated at
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
     * @var string|null The used smart meter
     *
     * @ODM\Field(type="string", name="used_smart_meter")
     */
    protected $usedSmartMeter;

    /**
     * @var int|null The v
     *
     * @ODM\Field(type="int", name="__v")
     */
    protected $v;

    /**
     * @var \DateTime|null The version date.
     *
     * @ODM\Field(type="date", name="version_date")
     */
    protected $versionDate;

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
     * Gets updatedAt.
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Gets updatedBy.
     *
     * @return string|null
     */
    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    /**
     * Gets createdAt.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Gets to Design.
     *
     * @return ToDesign|null
     */
    public function getToDesign(): ?ToDesign
    {
        return $this->to_design;
    }

    /**
     * Gets contractTypes.
     *
     * @return string[]
     */
    public function getContractTypes(): ?array
    {
        return $this->contractTypes;
    }

    /**
     * Gets applicationForUse.
     *
     * @return string[]
     */
    public function getApplicationForUse(): ?array
    {
        return $this->applicationForUse;
    }

    /**
     * Gets createdBy.
     *
     * @return string|null
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * Gets tariffCode.
     *
     * @return string
     */
    public function getTariffCode(): ?string
    {
        return $this->tariffCode;
    }

    /**
     * Gets tariffName.
     *
     * @return string|null
     */
    public function getTariffName(): ?string
    {
        return $this->tariffName;
    }

    /**
     * Gets tariff customized.
     *
     * @return bool|null
     */
    public function getTariffCustomized(): ?bool
    {
        return $this->tariffCustomized;
    }

    /**
     * Gets tariff internal only.
     *
     * @return bool|null
     */
    public function getTariffInternalOnly(): ?bool
    {
        return $this->tariffInternalOnly;
    }

    /**
     * Gets availableFrom.
     *
     * @return \DateTime|null
     */
    public function getAvailableFrom(): ?\DateTime
    {
        return $this->availableFrom;
    }

    /**
     * Gets tariffStartDate.
     *
     * @return string|null
     */
    public function getTariffStartDate(): ?string
    {
        return $this->tariffStartDate;
    }

    /**
     * Gets tariffEndDate.
     *
     * @return string|null
     */
    public function getTariffEndDate(): ?string
    {
        return $this->tariffEndDate;
    }

    /**
     * Gets minimum contract term.
     *
     * @return string|null
     */
    public function getMinContractTerm(): ?string
    {
        return $this->minContractTerm;
    }

    /**
     * Gets tariffDescription.
     *
     * @return string|null
     */
    public function getTariffDescription(): ?string
    {
        return $this->tariffDescription;
    }

    /**
     * Gets chargeDescription.
     *
     * @return string|null
     */
    public function getChargeDescription(): ?string
    {
        return $this->chargeDescription;
    }

    /**
     * Gets dailyRate.
     *
     * @return string[]|null
     */
    public function getDailyRate(): ?array
    {
        return $this->dailyRate;
    }

    /**
     * Gets isDailyRate.
     *
     * @return bool|null
     */
    public function getIsDailyRate(): ?bool
    {
        return $this->isDailyRate;
    }

    /**
     * Gets tariff status.
     *
     * @return string
     */
    public function getTariffStatus(): ?string
    {
        return $this->tariffStatus;
    }

    /**
     * Gets retailerName.
     *
     * @return string|null
     */
    public function getRetailerName(): ?string
    {
        return $this->retailerName;
    }

    /**
     * Gets versionDate.
     *
     * @return \DateTime|null
     */
    public function getVersionDate(): ?\DateTime
    {
        return $this->versionDate;
    }

    /**
     * Gets pricePlanNonStandard.
     *
     * @return string|null
     */
    public function getPricePlanNonStandard(): ?string
    {
        return $this->pricePlanNonStandard;
    }

    /**
     * Gets earlyTerminationCharge.
     *
     * @return string|null
     */
    public function getEarlyTerminationCharge(): ?string
    {
        return $this->earlyTerminationCharge;
    }

    /**
     * Gets bundledService.
     *
     * @return string|null
     */
    public function getBundledService(): ?string
    {
        return $this->bundledService;
    }

    /**
     * Gets otherFee.
     *
     * @return string|null
     */
    public function getOtherFee(): ?string
    {
        return $this->otherFee;
    }

    /**
     * Gets securityDeposit.
     *
     * @return string|null
     */
    public function getSecurityDeposit(): ?string
    {
        return $this->securityDeposit;
    }

    /**
     * Gets amiFee.
     *
     * @return string|null
     */
    public function getAmiFee(): ?string
    {
        return $this->amiFee;
    }

    /**
     * Gets latePaymentCharge.
     *
     * @return string|null
     */
    public function getLatePaymentCharge(): ?string
    {
        return $this->latePaymentCharge;
    }

    /**
     * Gets oneTimeFee.
     *
     * @return string|null
     */
    public function getOneTimeFee(): ?string
    {
        return $this->oneTimeFee;
    }

    /**
     * Gets retailerToBill.
     *
     * @return string|null
     */
    public function getRetailerToBill(): ?string
    {
        return $this->retailerToBill;
    }

    /**
     * Gets usedSmartMeter.
     *
     * @return string|null
     */
    public function getUsedSmartMeter(): ?string
    {
        return $this->usedSmartMeter;
    }

    /**
     * Gets contractRenewal.
     *
     * @return string|null
     */
    public function getContractRenewal(): ?string
    {
        return $this->contractRenewal;
    }

    /**
     * Gets contractDuration.
     *
     * @return string|null
     */
    public function getContractDuration(): ?string
    {
        return $this->contractDuration;
    }

    /**
     * Gets retailerIncentive.
     *
     * @return string|null
     */
    public function getRetailerIncentive(): ?string
    {
        return $this->retailerIncentive;
    }

    /**
     * Gets pricePlanStandard.
     *
     * @return string|null
     */
    public function getPricePlanStandard(): ?string
    {
        return $this->pricePlanStandard;
    }

    /**
     * Gets pricePlanType.
     *
     * @return string|null
     */
    public function getPricePlanType(): ?string
    {
        return $this->pricePlanType;
    }

    /**
     * Gets pricePlanName.
     *
     * @return string|null
     */
    public function getPricePlanName(): ?string
    {
        return $this->pricePlanName;
    }

    /**
     * Gets tariffRemark.
     *
     * @return string|null
     */
    public function getTariffRemark(): ?string
    {
        return $this->tariffRemark;
    }

    /**
     * @return string|null
     */
    public function getTariffRejectionNotes(): ?string
    {
        return $this->tariffRejectionNotes;
    }

    /**
     * Gets tariffLimit.
     *
     * @return int|null
     */
    public function getTariffLimit(): ?int
    {
        return $this->tariffLimit;
    }

    /**
     * Gets v.
     *
     * @return int|null
     */
    public function getV(): ?int
    {
        return $this->v;
    }
}
