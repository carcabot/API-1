<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="point_types")
 */
class PointType
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $_id;

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="amount")
     */
    protected $amount;

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
     * @var int
     *
     * @ODM\Field(type="int", name="point")
     */
    protected $point;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="point_category")
     */
    protected $pointCategory;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="id")
     */
    protected $pointId;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="type_of_point")
     */
    protected $pointType;

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
     * @var \DateTime|null The tariff updated at
     *
     * @ODM\Field(type="date", name="valid_from")
     */
    protected $validFrom;

    /**
     * @var \DateTime|null The tariff updated at
     *
     * @ODM\Field(type="date", name="valid_to")
     */
    protected $validThrough;

    /**
     * Gets id.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->_id;
    }

    /**
     * Gets amount.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
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
     * @return int
     */
    public function getPoint(): int
    {
        return $this->point;
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
     * Gets point id.
     *
     * @return string|null
     */
    public function getPointId(): ?string
    {
        return $this->pointId;
    }

    /**
     * Gets point type.
     *
     * @return string|null
     */
    public function getPointType(): ?string
    {
        return $this->pointType;
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

    /**
     * Gets valid from.
     *
     * @return \DateTime|null
     */
    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
     * Gets valid through.
     *
     * @return \DateTime|null
     */
    public function getValidThrough(): ?\DateTime
    {
        return $this->validThrough;
    }
}
