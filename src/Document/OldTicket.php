<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 31/1/19
 * Time: 6:24 PM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="cases")
 */
class OldTicket
{
    /**
     * @var string
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
     * @var \DateTime|null The actual complete date.
     *
     * @ODM\Field(type="date", name="actual_complete_date")
     */
    protected $actualCompleteDate;

    /**
     * @var \DateTime|null The actual completion date.
     *
     * @ODM\Field(type="date", name="actual_completion_date")
     */
    protected $actualCompletionDate;

    /**
     * @var \DateTime|null The actual response date.
     *
     * @ODM\Field(type="date", name="actual_response_date")
     */
    protected $actualResponseDate;

    /**
     * @var \DateTime|null The actual start date.
     *
     * @ODM\Field(type="date", name="actual_start_date")
     */
    protected $actualStartDate;

    /**
     * @var \DateTime|null The actual start date date.
     *
     * @ODM\Field(type="date", name="actual_start_date_date")
     */
    protected $actualStartDateDate;

    /**
     * @var string[]|null The attachments.
     *
     * @ODM\Field(type="collection", name="attachments")
     */
    protected $attachments;

    /**
     * @var string|null The channel.
     *
     * @ODM\Field(type="string", name="channel")
     */
    protected $channel;

    /**
     * @var string|null The complaint reference number.
     *
     * @ODM\Field(type="id", name="complaint_ref_no")
     */
    protected $complaintReferenceNumber;

    /**
     * @var string|null The contact email.
     *
     * @ODM\Field(type="string", name="contact_email")
     */
    protected $contactEmail;

    /**
     * @var string|null The contact name.
     *
     * @ODM\Field(type="string", name="contact_name")
     */
    protected $contactName;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldPhoneNumber",
     * name="contact_phone_no")
     */
    protected $contactPhoneNumber;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldPhoneNumber",
     * name="contact_mobile_no")
     */
    protected $contactMobileNumber;

    /**
     * @var \DateTime|null The ticket created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null The ticket created by
     *
     * @ODM\Field(type="id", name="_createdBy")
     */
    protected $createdBy;

    /**
     * @var string|null The customer account.
     *
     * @ODM\Field(type="string", name="customer_account")
     */
    protected $customerAccount;

    /**
     * @var string|null The customer account application id.
     *
     * @ODM\Field(type="string", name="customer_account_application_id")
     */
    protected $customerAccountApplicationId;

    /**
     * @var string|null The customer account contract id.
     *
     * @ODM\Field(type="string", name="customer_account_contract_id")
     */
    protected $customerAccountContractId;

    /**
     * @var string|null The contract Id.
     *
     * @ODM\Field(type="id", name="customer_account_no")
     */
    protected $contractId;

    /**
     * @var string|null The customer contact person id.
     *
     * @ODM\Field(type="id", name="customer_contact_person_id")
     */
    protected $customerContactPersonId;

    /**
     * @var string|null The customer id .
     *
     * @ODM\Field(type="id", name="customer_id")
     */
    protected $customerId;

    /**
     * @var string|null The department id.
     *
     * @ODM\Field(type="string", name="department_id")
     */
    protected $departmentId;

    /**
     * @var string|null The description.
     *
     * @ODM\Field(type="string", name="desc")
     */
    protected $description;

    /**
     * @var string|null The employee assigned.
     *
     * @ODM\Field(type="id", name="employee_assign")
     */
    protected $employeeAssign;

    /**
     * @var \DateTime|null The incident date.
     *
     * @ODM\Field(type="date", name="incident_date")
     */
    protected $incidentDate;

    /**
     * @var bool|null Is anonymous.
     *
     * @ODM\Field(type="bool", name="is_anonymous")
     */
    protected $isAnonymous;

    /**
     * @var string|null The main category.
     *
     * @ODM\Field(type="id", name="main_category")
     */
    protected $mainCategory;

    /**
     * @var string[]|null The note.
     *
     * @ODM\Field(type="collection", name="note")
     */
    protected $note;

    /**
     * @var \DateTime|null The plann complete date.
     *
     * @ODM\Field(type="date", name="plann_complete_date")
     */
    protected $planCompleteDate;

    /**
     * @var \DateTime|null The planned completion date.
     *
     * @ODM\Field(type="date", name="planned_completion_date")
     */
    protected $plannedCompletionDate;

    /**
     * @var string|null The priority.
     *
     * @ODM\Field(type="string", name="priority")
     */
    protected $priority;

    /**
     * @var string|null The resolution officer.
     *
     * @ODM\Field(type="string", name="resolution_officer")
     */
    protected $resolutionOfficer;

    /**
     * @var string|null The roles.
     *
     * @ODM\Field(type="string", name="roles")
     */
    protected $roles;

    /**
     * @var \DateTime|null The sla end date.
     *
     * @ODM\Field(type="date", name="sla_end_date")
     */
    protected $slaEndDate;

    /**
     * @var string|null The sla id.
     *
     * @ODM\Field(type="id", name="sla_id")
     */
    protected $slaId;

    /**
     * @var \DateTime|null The sla level 1 date.
     *
     * @ODM\Field(type="date", name="sla_level_1_date")
     */
    protected $slaLevel1Date;

    /**
     * @var \DateTime|null The sla level 2 date.
     *
     * @ODM\Field(type="date", name="sla_level_2_date")
     */
    protected $slaLevel2Date;

    /**
     * @var \DateTime|null The sla level 3 date.
     *
     * @ODM\Field(type="date", name="sla_level_3_date")
     */
    protected $slaLevel3Date;

    /**
     * @var string|null The sla timer.
     *
     * @ODM\Field(type="string", name="sla_timer")
     */
    protected $slaTimer;

    /**
     * @var int|null sla timer active count.
     *
     * @ODM\Field(type="int", name="sla_timer_active_count")
     */
    protected $slaTimerActiveCount;

    /**
     * @var string[]|null The sla timer customer action.
     *
     * @ODM\Field(type="collection", name="sla_timer_customer_action")
     */
    protected $slaTimerCustomerAction;

    /**
     * @var string|null The sla timer minutes.
     *
     * @ODM\Field(type="string", name="sla_timer_minutes")
     */
    protected $slaTimerMinutes;

    /**
     * @var bool|null sla timer status.
     *
     * @ODM\Field(type="bool", name="sla_timer_status")
     */
    protected $slaTimerStatus;

    /**
     * @var string|null The source.
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
     * @var string|null The sub category.
     *
     * @ODM\Field(type="id", name="sub_category")
     */
    protected $subCategory;

    /**
     * @var string The ticket id.
     *
     * @ODM\Field(type="string", name="_caseId")
     */
    protected $ticketId;

    /**
     * @var bool|null Ticket task indicator .
     *
     * @ODM\Field(type="bool", name="case_task_indicator")
     */
    protected $ticketTaskIndicator;

    /**
     * @var string|null The ticket type.
     *
     * @ODM\Field(type="id", name="case_type")
     */
    protected $ticketType;

    /**
     * @var \DateTime|null The ticket updated at
     *
     * @ODM\Field(type="date", name="_updatedAt")
     */
    protected $updatedAt;

    /**
     * @var string|null The ticket updated by
     *
     * @ODM\Field(type="id", name="_updatedBy")
     */
    protected $updatedBy;

    /**
     * @return string
     */
    public function getId(): string
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
     * @return \DateTime|null
     */
    public function getActualCompleteDate(): ?\DateTime
    {
        return $this->actualCompleteDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getActualCompletionDate(): ?\DateTime
    {
        return $this->actualCompletionDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getActualResponseDate(): ?\DateTime
    {
        return $this->actualResponseDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getActualStartDate(): ?\DateTime
    {
        return $this->actualStartDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getActualStartDateDate(): ?\DateTime
    {
        return $this->actualStartDateDate;
    }

    /**
     * @return string[]|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    /**
     * @return string|null
     */
    public function getChannel(): ?string
    {
        return $this->channel;
    }

    /**
     * @return string|null
     */
    public function getComplaintReferenceNumber(): ?string
    {
        return $this->complaintReferenceNumber;
    }

    /**
     * @return string|null
     */
    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    /**
     * @return string|null
     */
    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    /**
     * @return OldPhoneNumber|null
     */
    public function getContactMobileNumber()
    {
        return $this->contactMobileNumber;
    }

    /**
     * @return OldPhoneNumber|null
     */
    public function getContactPhoneNumber()
    {
        return $this->contactPhoneNumber;
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
    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    /**
     * @return string|null
     */
    public function getCustomerAccount(): ?string
    {
        return $this->customerAccount;
    }

    /**
     * @return string|null
     */
    public function getCustomerAccountApplicationId(): ?string
    {
        return $this->customerAccountApplicationId;
    }

    /**
     * @return string|null
     */
    public function getCustomerAccountContractId(): ?string
    {
        return $this->customerAccountContractId;
    }

    /**
     * @return string|null
     */
    public function getCustomerContactPersonId(): ?string
    {
        return $this->customerContactPersonId;
    }

    /**
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @return string|null
     */
    public function getDepartmentId(): ?string
    {
        return $this->departmentId;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getEmployeeAssign(): ?string
    {
        return $this->employeeAssign;
    }

    /**
     * @return \DateTime|null
     */
    public function getIncidentDate(): ?\DateTime
    {
        return $this->incidentDate;
    }

    /**
     * @return bool|null
     */
    public function getisAnonymous(): ?bool
    {
        return $this->isAnonymous;
    }

    /**
     * @return string|null
     */
    public function getMainCategory(): ?string
    {
        return $this->mainCategory;
    }

    /**
     * @return string[]|null
     */
    public function getNote(): ?array
    {
        return $this->note;
    }

    /**
     * @return \DateTime|null
     */
    public function getPlanCompleteDate(): ?\DateTime
    {
        return $this->planCompleteDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getPlannedCompletionDate(): ?\DateTime
    {
        return $this->plannedCompletionDate;
    }

    /**
     * @return string|null
     */
    public function getPriority(): ?string
    {
        return $this->priority;
    }

    /**
     * @return string|null
     */
    public function getResolutionOfficer(): ?string
    {
        return $this->resolutionOfficer;
    }

    /**
     * @return string|null
     */
    public function getRoles(): ?string
    {
        return $this->roles;
    }

    /**
     * @return \DateTime|null
     */
    public function getSlaEndDate(): ?\DateTime
    {
        return $this->slaEndDate;
    }

    /**
     * @return string|null
     */
    public function getSlaId(): ?string
    {
        return $this->slaId;
    }

    /**
     * @return \DateTime|null
     */
    public function getSlaLevel1Date(): ?\DateTime
    {
        return $this->slaLevel1Date;
    }

    /**
     * @return \DateTime|null
     */
    public function getSlaLevel2Date(): ?\DateTime
    {
        return $this->slaLevel2Date;
    }

    /**
     * @return \DateTime|null
     */
    public function getSlaLevel3Date(): ?\DateTime
    {
        return $this->slaLevel3Date;
    }

    /**
     * @return string|null
     */
    public function getSlaTimer(): ?string
    {
        return $this->slaTimer;
    }

    /**
     * @return int|null
     */
    public function getSlaTimerActiveCount(): ?int
    {
        return $this->slaTimerActiveCount;
    }

    /**
     * @return string[]|null
     */
    public function getSlaTimerCustomerAction(): ?array
    {
        return $this->slaTimerCustomerAction;
    }

    /**
     * @return string|null
     */
    public function getSlaTimerMinutes(): ?string
    {
        return $this->slaTimerMinutes;
    }

    /**
     * @return bool|null
     */
    public function getSlaTimerStatus(): ?bool
    {
        return $this->slaTimerStatus;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
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
    public function getSubCategory(): ?string
    {
        return $this->subCategory;
    }

    /**
     * @return string
     */
    public function getTicketId(): string
    {
        return $this->ticketId;
    }

    /**
     * @return bool|null
     */
    public function getTicketTaskIndicator(): ?bool
    {
        return $this->ticketTaskIndicator;
    }

    /**
     * @return string|null
     */
    public function getTicketType(): ?string
    {
        return $this->ticketType;
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
