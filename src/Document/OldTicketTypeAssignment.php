<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 8/2/19
 * Time: 11:43 AM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="case_type_assignments")
 */
class OldTicketTypeAssignment
{
    /**
     * @var string
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string|null The ticket type id.
     *
     * @ODM\Field(type="id", name="case_type_id")
     */
    protected $ticketTypeId;

    /**
     * @var string|null The ticket category type.
     *
     * @ODM\Field(type="string", name="category_type")
     */
    protected $ticketCategory;

    /**
     * @var string|null The ticket category id.
     *
     * @ODM\Field(type="id", name="category_id")
     */
    protected $ticketCategoryId;

    /**
     * @var \DateTime|null The ticket type assignment created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null The ticket type assignment created by
     *
     * @ODM\Field(type="id", name="_createdBy")
     */
    protected $createdBy;

    /**
     * @var \DateTime|null The ticket type assignment updated at
     *
     * @ODM\Field(type="date", name="_updatedAt")
     */
    protected $updatedAt;

    /**
     * @var string|null The ticket type assignment updated by
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
     * @return string|null
     */
    public function getTicketTypeId(): ?string
    {
        return $this->ticketTypeId;
    }

    /**
     * @return string|null
     */
    public function getTicketCategory(): ?string
    {
        return $this->ticketCategory;
    }

    /**
     * @return string|null
     */
    public function getTicketCategoryId(): ?string
    {
        return $this->ticketCategoryId;
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
