<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 4/2/19
 * Time: 10:22 AM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="case_assignments")
 */
class OldTicketAssignments
{
    /**
     * @var string
     *
     * @ODM\Id
     */
    protected $id;

    /**
     * @var \DateTime|null The ticket assignment created at
     *
     * @ODM\Field(type="date", name="_createdAt")
     */
    protected $createdAt;

    /**
     * @var string|null The ticket assignment created by
     *
     * @ODM\Field(type="id", name="_createdBy")
     */
    protected $createdBy;

    /**
     * @var string[]|null The criteria.
     *
     * @ODM\Field(type="collection", name="criteria")
     */
    protected $criteria;

    /**
     * @var string|null The department id.
     *
     * @ODM\Field(type="id", name="department_id")
     */
    protected $departmentId;

    /**
     * @var string|null The group name.
     *
     * @ODM\Field(type="string", name="group_name")
     */
    protected $groupName;

    /**
     * @var string|null The role id.
     *
     * @ODM\Field(type="id", name="role_id")
     */
    protected $roleId;

    /**
     * @var string|null The rule.
     *
     * @ODM\Field(type="string", name="rule")
     */
    protected $rule;

    /**
     * @var bool|null Status.
     *
     * @ODM\Field(type="bool", name="status")
     */
    protected $status;

    /**
     * @var string|null The user id.
     *
     * @ODM\Field(type="id", name="user_id")
     */
    protected $userId;

    /**
     * @var \DateTime|null The ticket assignment updated at
     *
     * @ODM\Field(type="date", name="_updatedAt")
     */
    protected $updatedAt;

    /**
     * @var string|null The ticket assignment updated by
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
    public function getCriteria(): ?array
    {
        return $this->criteria;
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
    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    /**
     * @return string|null
     */
    public function getRoleId(): ?string
    {
        return $this->roleId;
    }

    /**
     * @return string|null
     */
    public function getRule(): ?string
    {
        return $this->rule;
    }

    /**
     * @return bool|null
     */
    public function getStatus(): ?bool
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->userId;
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
