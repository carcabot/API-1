<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 16/1/19
 * Time: 9:09 AM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="leads")
 */
class OldLead
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string[]|null The activity.
     *
     * @ODM\Field(type="collection", name="activity")
     */
    protected $activity;

    /**
     * @var string|null The average consumption
     *
     * @ODM\Field(type="id", name="average_consumption")
     */
    protected $averageConsumption;

    /**
     * @var string|null The category.
     *
     * @ODM\Field(type="string", name="category")
     */
    protected $category;

    /**
     * @var int|null The consumption amount
     *
     * @ODM\Field(type="int", name="consumption_amount")
     */
    protected $consumptionAmount;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldContactPerson",
     * name="contact_person")
     */
    protected $contactPerson;

    /**
     * @var string|null The contract type
     *
     * @ODM\Field(type="string", name="contract_type")
     */
    protected $contractType;

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
     * @var string|null The dwelling type.
     *
     * @ODM\Field(type="string", name="dwelling_type")
     */
    protected $dwellingType;

    /**
     * @var string|null The employee response id
     *
     * @ODM\Field(type="id", name="employee_respon_id")
     */
    protected $employeeResponseId;

    /**
     * @var \DateTime|null Follow up date
     *
     * @ODM\Field(type="date", name="follow_up_date")
     */
    protected $followUpDate;

    /**
     * @var string|null The indicate.
     *
     * @ODM\Field(type="string", name="indicate")
     */
    protected $indicate;

    /**
     * @var bool|null Is existing customer .
     *
     * @ODM\Field(type="bool", name="is_existing_customer")
     */
    protected $existingCustomer;

    /**
     * @var string|null The lead Id
     *
     * @ODM\Field(type="string", name="_leadId")
     */
    protected $leadId;

    /**
     * @var string|null The meter type.
     *
     * @ODM\Field(type="string", name="meter_type")
     */
    protected $meterType;

    /**
     * @ODM\Field(type="collection", name="note")
     */
    protected $note;

    /**
     * @var string|null The partner id
     *
     * @ODM\Field(type="id", name="partner_id")
     */
    protected $partnerId;

    /**
     * @var int|null The purchase time frame.
     *
     * @ODM\Field(type="int", name="purchase_time_frame")
     */
    protected $purchaseTimeFrame;

    /**
     * @var string|null The quotations
     *
     * @ODM\Field(type="id", name="quotations")
     */
    protected $quotations;

    /**
     * @var string|null The rate.
     *
     * @ODM\Field(type="string", name="rate")
     */
    protected $rate;

    /**
     * @var string|null Lead reason
     *
     * @ODM\Field(type="string", name="reason")
     */
    protected $reason;

    /**
     * @var string|null The reference source.
     *
     * @ODM\Field(type="string", name="ref_source")
     */
    protected $referenceSource;

    /**
     * @var string|null The sales rep id
     *
     * @ODM\Field(type="id", name="sale_rep_id")
     */
    protected $saleRepId;

    /**
     * @var string The source.
     *
     * @ODM\Field(type="string", name="source")
     */
    protected $source;

    /**
     * @var string The status.
     *
     * @ODM\Field(type="string", name="status")
     */
    protected $status;

    /**
     * @var string|null The tariff rate id
     *
     * @ODM\Field(type="id", name="promotion_code_id")
     */
    protected $tariffRateId;

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
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string[]|null
     */
    public function getActivity(): ?array
    {
        return $this->activity;
    }

    /**
     * @return string|null
     */
    public function getAverageConsumption(): ?string
    {
        return $this->averageConsumption;
    }

    /**
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @return int|null
     */
    public function getConsumptionAmount(): ?int
    {
        return $this->consumptionAmount;
    }

    /**
     * @return OldContactPerson|null
     */
    public function getContactPerson()
    {
        return $this->contactPerson;
    }

    /**
     * @return string|null
     */
    public function getContractType(): ?string
    {
        return $this->contractType;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return string|null
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * @return string|null
     */
    public function getDwellingType(): ?string
    {
        return $this->dwellingType;
    }

    /**
     * @return string|null
     */
    public function getEmployeeResponseId(): ?string
    {
        return $this->employeeResponseId;
    }

    /**
     * @return bool|null
     */
    public function getExistingCustomer(): ?bool
    {
        return $this->existingCustomer;
    }

    /**
     * @return \DateTime|null
     */
    public function getFollowUpDate(): ?\DateTime
    {
        return $this->followUpDate;
    }

    /**
     * @return string|null
     */
    public function getIndicate(): ?string
    {
        return $this->indicate;
    }

    /**
     * @return string|null
     */
    public function getLeadId(): ?string
    {
        return $this->leadId;
    }

    /**
     * @return string|null
     */
    public function getMeterType(): ?string
    {
        return $this->meterType;
    }

    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return string|null
     */
    public function getPartnerId(): ?string
    {
        return $this->partnerId;
    }

    /**
     * @return int|null
     */
    public function getPurchaseTimeFrame(): ?int
    {
        return $this->purchaseTimeFrame;
    }

    /**
     * @return string|null
     */
    public function getQuotations(): ?string
    {
        return $this->quotations;
    }

    /**
     * @return string|null
     */
    public function getRate(): ?string
    {
        return $this->rate;
    }

    /**
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @return string|null
     */
    public function getReferenceSource(): ?string
    {
        return $this->referenceSource;
    }

    /**
     * @return string|null
     */
    public function getSaleRepId(): ?string
    {
        return $this->saleRepId;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getTariffRateId(): ?string
    {
        return $this->tariffRateId;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return string|null
     */
    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }
}
