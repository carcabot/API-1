<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"department_read"}},
 *     "denormalization_context"={"groups"={"department_write"}},
 * })
 */
class Department
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @var Collection<Department> A child of the item.
     *
     * @ORM\OneToMany(targetEntity="Department", cascade={"persist"}, mappedBy="parent")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $children;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var bool The enabled indicator.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @ApiProperty()
     */
    protected $enabled;

    /**
     * @var string The name of the item.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var Department|null
     *
     * @ORM\ManyToOne(targetEntity="Department", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * @ApiProperty()
     */
    protected $parent;

    /**
     * @var Collection<Role>
     *
     * @ORM\OneToMany(targetEntity="Role", cascade={"persist"}, mappedBy="department")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $roles;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->roles = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Adds child.
     *
     * @param Department $child
     *
     * @return $this
     */
    public function addChild(self $child)
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * Removes child.
     *
     * @param Department $child
     *
     * @return $this
     */
    public function removeChild(self $child)
    {
        $this->children->removeElement($child);

        return $this;
    }

    /**
     * Gets children.
     *
     * @return Department[]
     */
    public function getChildren(): array
    {
        return $this->children->getValues();
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Department|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param Department|null $parent
     *
     * @return $this
     */
    public function setParent(?self $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Adds role.
     *
     * @param Role $role
     *
     * @return $this
     */
    public function addRole(Role $role)
    {
        $this->roles[] = $role;

        return $this;
    }

    /**
     * Removes role.
     *
     * @param Role $role
     *
     * @return $this
     */
    public function removeRole(Role $role)
    {
        $this->roles->removeElement($role);

        return $this;
    }

    /**
     * Gets roles.
     *
     * @return Role[]
     */
    public function getRoles(): array
    {
        return $this->roles->getValues();
    }
}
