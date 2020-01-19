<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="customer_ids")
 */
class OldCustomerIds
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
     * @var bool|null The date prefix
     *
     * @ODM\Field(type="bool", name="date_prefix")
     */
    protected $datePrefix;

    /**
     * @var int|null The increment
     *
     * @ODM\Field(type="int", name="increment")
     */
    protected $increment;

    /**
     * @var bool|null Is id active.
     *
     * @ODM\Field(type="bool", name="is_active")
     */
    protected $isActive;

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
     * @var string|null The separator.
     *
     * @ODM\Field(type="string", name="separator")
     */
    protected $separator;

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
     * @return bool|null
     */
    public function getDatePrefix(): ?bool
    {
        return $this->datePrefix;
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
     * @return string|null
     */
    public function getSeparator(): ?string
    {
        return $this->separator;
    }
}
