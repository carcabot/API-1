<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Role.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"role_read"}},
 *     "denormalization_context"={"groups"={"role_write"}},
 * })
 */
class Role
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
     * @var Collection<Role> A child of the item.
     *
     * @ORM\OneToMany(targetEntity="Role", cascade={"persist"}, mappedBy="parent")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $children;

    /**
     * @var Department|null Department in which roles belong to.
     *
     * @ORM\ManyToOne(targetEntity="Department", inversedBy="roles")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/customer")
     */
    protected $department;

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
     * @var Role|null
     *
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * @ApiProperty()
     */
    protected $parent;

    /**
     * @var Collection<Profile>
     *
     * @ORM\ManyToMany(targetEntity="Profile", cascade={"persist"})
     * @ORM\JoinTable(
     *  joinColumns={@ORM\JoinColumn(name="role_id", onDelete="CASCADE")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="profile_id", onDelete="CASCADE")}
     * )
     *
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $profiles;

    /**
     * @var bool The profile privilege indicator.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @ApiProperty()
     */
    protected $profilePrivilege;

    /**
     * @var Collection<RoleProfileModule>
     *
     * @ORM\OneToMany(targetEntity="RoleProfileModule", cascade={"persist"}, mappedBy="role")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $roleProfileModules;

    /**
     * @var Collection<User>
     *
     * @ORM\ManyToMany(targetEntity="User", mappedBy="userRoles")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $users;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->roleProfileModules = new ArrayCollection();
        $this->profiles = new ArrayCollection();
        $this->users = new ArrayCollection();
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
     * @param Role $child
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
     * @param Role $child
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
     * @return Role[]
     */
    public function getChildren(): array
    {
        return $this->children->getValues();
    }

    /**
     * @return Department|null
     */
    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    /**
     * @param Department $department
     *
     * @return $this
     */
    public function setDepartment(?Department $department)
    {
        $this->department = $department;

        return $this;
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
    public function getEnabled(): bool
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
     * @return Role|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param Role|null $parent
     *
     * @return $this
     */
    public function setParent(?self $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Adds profile.
     *
     * @param Profile $profile
     *
     * @return $this
     */
    public function addProfile(Profile $profile)
    {
        $this->profiles[] = $profile;

        return $this;
    }

    /**
     * Removes profile.
     *
     * @param Profile $profile
     *
     * @return $this
     */
    public function removeProfile(Profile $profile)
    {
        $this->profiles->removeElement($profile);

        return $this;
    }

    /**
     * Gets profiles.
     *
     * @return Profile[]
     */
    public function getProfiles(): array
    {
        return $this->profiles->getValues();
    }

    /**
     * @return bool
     */
    public function isProfilePrivilege(): bool
    {
        return $this->profilePrivilege;
    }

    /**
     * @param bool $profilePrivilege
     *
     * @return $this
     */
    public function setProfilePrivilege(bool $profilePrivilege)
    {
        $this->profilePrivilege = $profilePrivilege;

        return $this;
    }

    /**
     * Adds roleProfileModule.
     *
     * @param RoleProfileModule $roleProfileModule
     *
     * @return $this
     */
    public function addRoleProfileModule(RoleProfileModule $roleProfileModule)
    {
        $this->roleProfileModules[] = $roleProfileModule;

        return $this;
    }

    /**
     * Removes roleProfileModule.
     *
     * @param RoleProfileModule $roleProfileModule
     *
     * @return $this
     */
    public function removeRoleProfileModule(RoleProfileModule $roleProfileModule)
    {
        $this->roleProfileModules->removeElement($roleProfileModule);

        return $this;
    }

    /**
     * Gets roleProfileModules.
     *
     * @return RoleProfileModule[]
     */
    public function getRoleProfileModules(): array
    {
        return $this->roleProfileModules->getValues();
    }

    /**
     * Adds user.
     *
     * @param User $user
     *
     * @return $this
     */
    public function addUser(User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Removes user.
     *
     * @param User $user
     *
     * @return $this
     */
    public function removeUser(User $user)
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * Gets users.
     *
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->users->getValues();
    }
}
