<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="customers")
 */
class OldCustomerAccount
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string[]|null The attachments.
     *
     * @ODM\Field(type="collection", name="attachments")
     */
    protected $attachments;

    /**
     * @var string[]|null The activity.
     *
     * @ODM\Field(type="collection", name="activity")
     */
    protected $activity;

    /**
     * @var string The category.
     *
     * @ODM\Field(type="string", name="category")
     */
    protected $category;

    /**
     * @ODM\EmbedOne(
     * targetDocument="OldContactPerson",
     * name="contact_person")
     */
    protected $contactPerson;

    /**
     * @var \DateTime|null The customer account created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null The customer account created by
     *
     * @ODM\Field(type="id", name="_createdBy")
     */
    protected $createdBy;

    /**
     * @var string|null The customer account id.
     *
     * @ODM\Field(type="string", name="_customerId")
     */
    protected $customerId;

    /**
     * @var string|null The external customer account id.
     *
     * @ODM\Field(type="string", name="_externalCustomerId")
     */
    protected $externalCustomerId;

    /**
     * @var string|null The indicate.
     *
     * @ODM\Field(type="string", name="indicate")
     */
    protected $indicate;

    /**
     * @var bool|null Is black listed .
     *
     * @ODM\Field(type="bool", name="is_blacklist")
     */
    protected $blackListed;

    /**
     * @var bool|null Is prospect.
     *
     * @ODM\Field(type="bool", name="is_prospect")
     */
    protected $prospect;

    /**
     * @var string[]|null The note.
     *
     * @ODM\Field(type="collection", name="note")
     */
    protected $note;

    /**
     * @var string|null The reference source.
     *
     * @ODM\Field(type="string", name="ref_source")
     */
    protected $refSource;

    /**
     * @var string|null The referral code.
     *
     * @ODM\Field(type="string", name="referralCode")
     */
    protected $referralCode;

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
     * @var string[] The type.
     *
     * @ODM\Field(type="collection", name="type")
     */
    protected $type;

    /**
     * @var \DateTime|null The user updated at
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
     * @var int|null The v
     *
     * @ODM\Field(type="int", name="__v")
     */
    protected $v;

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
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    /**
     * Get activity.
     *
     * @return string[]|null
     */
    public function getActivity(): ?array
    {
        return $this->activity;
    }

    /**
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @return OldContactPerson|null
     */
    public function getContactPerson(): ?OldContactPerson
    {
        return $this->contactPerson;
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
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @return string|null
     */
    public function getExternalCustomerId(): ?string
    {
        return $this->externalCustomerId;
    }

    /**
     * @return string|null
     */
    public function getIndicate(): ?string
    {
        return $this->indicate;
    }

    /**
     * @return bool|null
     */
    public function getBlackListed(): ?bool
    {
        return $this->blackListed;
    }

    /**
     * @return bool|null
     */
    public function getProspect(): ?bool
    {
        return $this->prospect;
    }

    /**
     * @return string[]|null
     */
    public function getNote(): ?array
    {
        return $this->note;
    }

    /**
     * @return string|null
     */
    public function getRefSource(): ?string
    {
        return $this->refSource;
    }

    /**
     * @return string|null
     */
    public function getReferralCode(): ?string
    {
        return $this->referralCode;
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
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return string[]
     */
    public function getType(): ?array
    {
        return $this->type;
    }

    /**
     * @return int|null
     */
    public function getV(): ?int
    {
        return $this->v;
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
