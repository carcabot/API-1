<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 4/2/19
 * Time: 10:57 AM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="case_types")
 */
class OldTicketType
{
    /**
     * @var string
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var \DateTime|null The ticket type created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null The ticket type created by
     *
     * @ODM\Field(type="id", name="_createdBy")
     */
    protected $createdBy;

    /**
     * @var string[]|null The category.
     *
     * @ODM\Field(type="collection", name="category")
     */
    protected $category;

    /**
     * @var string|null The name.
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
     * @var \DateTime|null The ticket type updated at
     *
     * @ODM\Field(type="date", name="_updatedAt")
     */
    protected $updatedAt;

    /**
     * @var string|null The ticket type updated by
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
     * @return string[]|null
     */
    public function getCategory(): ?array
    {
        return $this->category;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
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
