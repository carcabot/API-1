<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\Role;
use Doctrine\ORM\Mapping as ORM;

/**
 * A subclass of OrganizationRole used to describe employee relationships.
 *
 * @see http://schema.org/EmployeeRole
 *
 * @ORM\Entity(repositoryClass="App\Repository\EmployeeRoleRepository")
 * @ApiResource(iri="http://schema.org/EmployeeRole", attributes={
 *     "normalization_context"={"groups"={"employee_role_read"}},
 *     "denormalization_context"={"groups"={"employee_role_write"}},
 *     "filters"={
 *         "employee_role.search",
 *     },
 * })
 */
class EmployeeRole
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var CustomerAccount Someone working for this organization. Supersedes [employees](http://schema.org/employees).
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ApiProperty(iri="http://schema.org/employee")
     */
    protected $employee;

    /**
     * @var \DateTime|null The end date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/endDate")
     */
    protected $endDate;

    /**
     * @var Role|null A role played, performed or filled by a person or organization. For example, the team of creators for a comic book might fill the roles named 'inker', 'penciller', and 'letterer'; or an athlete in a SportsTeam might play in the position named 'Quarterback'. Supersedes [namedPosition](http://schema.org/namedPosition).
     *
     * @ORM\Column(type="role_enum", nullable=true)
     * @ApiProperty(iri="http://schema.org/roleName")
     */
    protected $roleName;

    /**
     * @var \DateTime|null The start date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/startDate")
     */
    protected $startDate;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;

            if (null !== $this->endDate) {
                $this->endDate = clone $this->endDate;
            }

            if (null !== $this->startDate) {
                $this->startDate = clone $this->startDate;
            }
        }
    }

    /**
     * Gets id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets employee.
     *
     * @param CustomerAccount $employee
     *
     * @return $this
     */
    public function setEmployee(CustomerAccount $employee)
    {
        $this->employee = $employee;

        return $this;
    }

    /**
     * Gets employee.
     *
     * @return CustomerAccount
     */
    public function getEmployee(): CustomerAccount
    {
        return $this->employee;
    }

    /**
     * Sets endDate.
     *
     * @param \DateTime|null $endDate
     *
     * @return $this
     */
    public function setEndDate(?\DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Gets endDate.
     *
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * Sets roleName.
     *
     * @param Role|null $roleName
     *
     * @return $this
     */
    public function setRoleName(?Role $roleName)
    {
        $this->roleName = $roleName;

        return $this;
    }

    /**
     * Gets roleName.
     *
     * @return Role|null
     */
    public function getRoleName(): ?Role
    {
        return $this->roleName;
    }

    /**
     * Sets startDate.
     *
     * @param \DateTime|null $startDate
     *
     * @return $this
     */
    public function setStartDate(?\DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Gets startDate.
     *
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }
}
