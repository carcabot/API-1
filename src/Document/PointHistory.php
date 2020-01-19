<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="point_histories")
 */
class PointHistory
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="contract_id")
     */
    protected $contract;

    /**
     * @var \DateTime|null The tariff created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date", name="date")
     */
    protected $date;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="description")
     */
    protected $description;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="point_id")
     */
    protected $pointId;

    /**
     * @var int
     *
     * @ODM\Field(type="int", name="points")
     */
    protected $points;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="type_of_point")
     */
    protected $pointType;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="transaction_type")
     */
    protected $transactionType;

    /**
     * @var int|null
     *
     * @ODM\Field(type="int", name="total_points")
     */
    protected $totalPoints;

    /**
     * @ODM\EmbedOne(targetDocument="Reference")
     */
    protected $reference;

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
     * Gets contract.
     *
     * @return string|null
     */
    public function getContract(): ?string
    {
        return $this->contract;
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
     * Gets date.
     *
     * @return \DateTime|null
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
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
     * Gets point id.
     *
     * @return string|null
     */
    public function getPointId(): ?string
    {
        return $this->pointId;
    }

    /**
     * Gets point.
     *
     * @return int
     */
    public function getPoints(): int
    {
        return $this->points;
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
     * Gets transaction type.
     *
     * @return string|null
     */
    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    /**
     * Gets total points.
     *
     * @return int|null
     */
    public function getTotalPoints(): ?int
    {
        return $this->totalPoints;
    }

    /**
     * Gets reference.
     *
     * @return Reference
     */
    public function getReference()
    {
        return $this->reference;
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
