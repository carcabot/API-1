<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\ModuleCategory;
use App\Enum\ModuleType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"module_read"}},
 *     "denormalization_context"={"groups"={"module_write"}},
 * })
 */
class Module
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
     * @var Collection<Module> A child of the item.
     *
     * @ORM\OneToMany(targetEntity="Module", cascade={"persist"}, mappedBy="parent")
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
     * @var Module|null
     *
     * @ORM\ManyToOne(targetEntity="Module", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * @ApiProperty()
     */
    protected $parent;

    /**
     * Module constructor.
     */
    public function __construct()
    {
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
     * @param Module $child
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
     * @param Module $child
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
     * @return Module[]
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
     * @return Module|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param Module|null $parent
     *
     * @return $this
     */
    public function setParent(?self $parent)
    {
        $this->parent = $parent;

        return $this;
    }
}
