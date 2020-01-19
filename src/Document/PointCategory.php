<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="point_categories")
 */
class PointCategory
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

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
     * @var string|null
     *
     * @ODM\Field(type="string", name="description")
     */
    protected $description;

    /**
     * @var bool|null
     * @ODM\Field(type="bool", name="editable")
     */
    protected $editable;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="point_category")
     */
    protected $pointCategory;

    /**
     * @var int|null
     *
     * @ODM\Field(type="int", name="point_expiration")
     */
    protected $pointExpiration;

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
     * @var int|null The v
     *
     * @ODM\Field(type="int", name="__v")
     */
    protected $v;

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
     * Gets created at.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * Gets created by.
     *
     * @return string|null
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * Gets description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Gets editable.
     *
     * @return bool|null
     */
    public function getEditable(): ?bool
    {
        return $this->editable;
    }

    /**
     * Gets point category.
     *
     * @return string|null
     */
    public function getPointCategory(): ?string
    {
        return $this->pointCategory;
    }

    /**
     * Gets point expiration.
     *
     * @return int|null
     */
    public function getPointExpiration(): ?int
    {
        return $this->pointExpiration;
    }

    /**
     * Gets updated at.
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Gets updated by.
     *
     * @return string|null
     */
    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
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
