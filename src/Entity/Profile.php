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
 *     "normalization_context"={"groups"={"profile_read"}},
 *     "denormalization_context"={"groups"={"profile_write"}},
 * })
 */
class Profile
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
     * @var Collection<Profile> A child of the item.
     *
     * @ORM\OneToMany(targetEntity="Profile", cascade={"persist"}, mappedBy="parent")
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
     * @var Profile|null
     *
     * @ORM\ManyToOne(targetEntity="Profile", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * @ApiProperty()
     */
    protected $parent;
    /**
     * @var Collection<RoleProfileModule>
     *
     * @ORM\OneToMany(targetEntity="RoleProfileModule", cascade={"persist"}, mappedBy="profile")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $roleProfileModules;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->roleProfileModules = new ArrayCollection();
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
     * @param Profile $child
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
     * @param Profile $child
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
     * @return Profile[]
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
     * @return Profile|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param Profile|null $parent
     *
     * @return $this
     */
    public function setParent(?self $parent)
    {
        $this->parent = $parent;

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
        $roleProfileModule->setProfile($this);

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
}
