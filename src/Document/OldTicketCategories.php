<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 4/2/19
 * Time: 10:11 AM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="case_categories")
 */
class OldTicketCategories
{
    /**
     * @var string
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var \DateTime|null The ticket category created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null The ticket category created by
     *
     * @ODM\Field(type="id", name="_createdBy")
     */
    protected $createdBy;

    /**
     * @var string|null The description.
     *
     * @ODM\Field(type="string", name="desc")
     */
    protected $description;

    /**
     * @var string|null The main category.
     *
     * @ODM\Field(type="id", name="main_category")
     */
    protected $mainCategory;

    /**
     * @var string The name.
     *
     * @ODM\Field(type="string", name="name")
     */
    protected $name;

    /**
     * @var bool|null Status.
     *
     * @ODM\Field(type="bool", name="status")
     */
    protected $status;

    /**
     * @var bool|null Task Indicator.
     *
     * @ODM\Field(type="bool", name="task_indicator")
     */
    protected $taskIndicator;

    /**
     * @var string The type.
     *
     * @ODM\Field(type="string", name="type")
     */
    protected $type;

    /**
     * @var \DateTime|null The ticket category updated at
     *
     * @ODM\Field(type="date", name="_updatedAt")
     */
    protected $updatedAt;

    /**
     * @var string|null The ticket category updated by
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
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getMainCategory(): ?string
    {
        return $this->mainCategory;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool|null
     */
    public function getStatus(): ?bool
    {
        return $this->status;
    }

    /**
     * @return bool|null
     */
    public function getTaskIndicator(): ?bool
    {
        return $this->taskIndicator;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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
