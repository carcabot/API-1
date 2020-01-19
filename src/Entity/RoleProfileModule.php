<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\ModuleCategory;
use App\Enum\ModuleType;
use App\Enum\Permission;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"role_profile_module_read"}},
 *     "denormalization_context"={"groups"={"role_profile_module_write"}},
 *     "filters"={
 *         "role_profile_module.search",
 *     },
 * })
 */
class RoleProfileModule
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
     * @var ModuleCategory The category of the module.
     *
     * @ORM\Column(type="module_category_enum", nullable=false)
     * @ApiProperty()
     */
    protected $category;

    /**
     * @var Collection<RoleProfileModule> A child of the item.
     *
     * @ORM\OneToMany(targetEntity="RoleProfileModule", cascade={"persist"}, mappedBy="parent")
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
     * @var ModuleType The name of the module.
     *
     * @ORM\Column(type="module_type_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var RoleProfileModule|null
     *
     * @ORM\ManyToOne(targetEntity="RoleProfileModule", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * @ApiProperty()
     */
    protected $parent;

    /**
     * @var string[] The permissions of a role toward a module.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $permissions;

    /**
     * @var Profile|null
     *
     * @ORM\ManyToOne(targetEntity="Profile", inversedBy="roleProfileModules")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $profile;

    /**
     * @var Role|null
     *
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="roleProfileModules")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $role;

    /**
     * RoleProfileModule constructor.
     */
    public function __construct()
    {
        $this->permissions = [];
        $this->children = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ModuleCategory
     */
    public function getCategory(): ModuleCategory
    {
        return $this->category;
    }

    /**
     * @param ModuleCategory $category
     *
     * @return $this
     */
    public function setCategory(ModuleCategory $category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Adds child.
     *
     * @param RoleProfileModule $child
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
     * @param RoleProfileModule $child
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
     * @return RoleProfileModule[]
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
     * @return ModuleType
     */
    public function getName(): ModuleType
    {
        return $this->name;
    }

    /**
     * @param ModuleType $name
     */
    public function setName(ModuleType $name)
    {
        $this->name = $name;
    }

    /**
     * @return RoleProfileModule|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param RoleProfileModule|null $parent
     *
     * @return $this
     */
    public function setParent(?self $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Adds permission.
     *
     * @param string $permission
     *
     * @return $this
     */
    public function addPermission(string $permission)
    {
        $this->permissions[] = $permission;

        return $this;
    }

    /**
     * Removes permission.
     *
     * @param string $permission
     *
     * @return $this
     */
    public function removePermission(string $permission)
    {
        if (false !== ($key = \array_search($permission, $this->permissions, true))) {
            \array_splice($this->permissions, $key, 1);
        }

        return $this;
    }

    /**
     * Gets permissions.
     *
     * @return string[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return Profile|null
     */
    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    /**
     * @param Profile $profile
     *
     * @return $this
     */
    public function setProfile(?Profile $profile)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @return Role|null
     */
    public function getRole(): ?Role
    {
        return $this->role;
    }

    /**
     * @param Role $role
     *
     * @return $this
     */
    public function setRole(?Role $role)
    {
        $this->role = $role;

        return $this;
    }
}
