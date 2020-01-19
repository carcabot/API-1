<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 19/1/19
 * Time: 3:11 PM.
 */

namespace App\Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="application_ids")
 */
class OldApplicationIds
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var int|null The counter padding
     *
     * @ODM\Field(type="int", name="counter_padding")
     */
    protected $counterPadding;

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
     * @var bool|null The date prefix
     *
     * @ODM\Field(type="bool", name="date_prefix")
     */
    protected $datePrefix;

    /**
     * @var string|null The type.
     *
     * @ODM\Field(type="string", name="for_type")
     */
    protected $type;

    /**
     * @var int|null The increment
     *
     * @ODM\Field(type="int", name="increment")
     */
    protected $increment;

    /**
     * @var bool|null Is application id active.
     *
     * @ODM\Field(type="bool", name="is_active")
     */
    protected $isActive;

    /**
     * @var \DateTime|null The latest update.
     *
     * @ODM\Field(type="date", name="latest_update")
     */
    protected $latestUpdate;

    /**
     * @var int|null The next number
     *
     * @ODM\Field(type="int", name="next_number")
     */
    protected $nextNumber;

    /**
     * @var string|null The prefix.
     *
     * @ODM\Field(type="string", name="prefix")
     */
    protected $prefix;

    /**
     * @var int|null The reset counter
     *
     * @ODM\Field(type="int", name="reset_counter")
     */
    protected $resetCounter;

    /**
     * @var string|null The separator.
     *
     * @ODM\Field(type="string", name="separator")
     */
    protected $separator;

    /**
     * @var int|null The start number
     *
     * @ODM\Field(type="int", name="start_number")
     */
    protected $startNumber;

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
     * @return int|null
     */
    public function getCounterPadding(): ?int
    {
        return $this->counterPadding;
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
     * @return bool|null
     */
    public function getDatePrefix(): ?bool
    {
        return $this->datePrefix;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return int|null
     */
    public function getIncrement(): ?int
    {
        return $this->increment;
    }

    /**
     * @return bool|null
     */
    public function getisActive(): ?bool
    {
        return $this->isActive;
    }

    /**
     * @return \DateTime|null
     */
    public function getLatestUpdate(): ?\DateTime
    {
        return $this->latestUpdate;
    }

    /**
     * @return int|null
     */
    public function getNextNumber(): ?int
    {
        return $this->nextNumber;
    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @return int|null
     */
    public function getResetCounter(): ?int
    {
        return $this->resetCounter;
    }

    /**
     * @return string|null
     */
    public function getSeparator(): ?string
    {
        return $this->separator;
    }

    /**
     * @return int|null
     */
    public function getStartNumber(): ?int
    {
        return $this->startNumber;
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
