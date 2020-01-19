<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\TicketCategoryType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The ticket category.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"ticket_category_read"}},
 *     "denormalization_context"={"groups"={"ticket_category_write"}},
 *     "filters"={
 *         "ticket_category.exists",
 *         "ticket_category.order",
 *         "ticket_category.search",
 *     },
 * })
 */
class TicketCategory
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
     * @var Collection<TicketCategory> A child of the item.
     *
     * @ORM\OneToMany(targetEntity="TicketCategory", mappedBy="parent")
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $children;

    /**
     * @var string Ticket category code.
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     * @ApiProperty()
     */
    protected $code;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var bool|null The enabled indicator.
     *
     * @ORM\Column(type="boolean", nullable=true)
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
     * @var TicketCategory The ticket category.
     *
     * @ORM\ManyToOne(targetEntity="TicketCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * @ApiProperty()
     */
    protected $parent;

    /**
     * @var bool|null The task indicator.
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty()
     */
    protected $taskIndicator;

    /**
     * @var Collection<TicketType> An ticket type.
     *
     * @ORM\ManyToMany(targetEntity="TicketType", mappedBy="ticketCategories", cascade={"persist"})
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $ticketTypes;

    /**
     * @var TicketCategoryType A category type.
     *
     * @ORM\Column(type="ticket_category_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->ticketTypes = new ArrayCollection();
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
     * Adds child.
     *
     * @param TicketCategory $child
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
     * @param TicketCategory $child
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
     * @return TicketCategory[]
     */
    public function getChildren(): array
    {
        return $this->children->getValues();
    }

    /**
     * Gets code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Sets code.
     *
     * @param string $code
     *
     * @return $this
     */
    public function setCode(string $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Sets description.
     *
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
     * Gets description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets enabled.
     *
     * @param bool|null $enabled
     *
     * @return $this
     */
    public function setEnabled(?bool $enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Gets enabled.
     *
     * @return bool|null
     */
    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * Sets name.
     *
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
     * Gets name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets parent.
     *
     * @param TicketCategory $parent
     *
     * @return TicketCategory|null
     */
    public function setParent(?self $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Gets parent.
     *
     * @return TicketCategory|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * Sets taskIndicator.
     *
     * @param bool|null $taskIndicator
     *
     * @return $this
     */
    public function setTaskIndicator(?bool $taskIndicator)
    {
        $this->taskIndicator = $taskIndicator;

        return $this;
    }

    /**
     * Gets taskIndicator.
     *
     * @return bool|null
     */
    public function isTaskIndicator(): ?bool
    {
        return $this->taskIndicator;
    }

    /**
     * Adds ticketTypes.
     *
     * @param TicketType $ticketTypes
     *
     * @return $this
     */
    public function addTicketType(TicketType $ticketTypes)
    {
        $ticketTypes->addTicketCategory($this);
        $this->ticketTypes[] = $ticketTypes;

        return $this;
    }

    /**
     * Clears ticketTypes.
     *
     * @return $this
     */
    public function clearTicketTypes()
    {
        foreach ($this->getTicketTypes() as $ticketType) {
            $this->removeTicketType($ticketType);
        }

        return $this;
    }

    /**
     * Removes ticketTypes.
     *
     * @param TicketType $ticketTypes
     *
     * @return $this
     */
    public function removeTicketType(TicketType $ticketTypes)
    {
        $ticketTypes->removeTicketCategory($this);
        $this->ticketTypes->removeElement($ticketTypes);

        return $this;
    }

    /**
     * Gets ticketTypes.
     *
     * @return TicketType[]
     */
    public function getTicketTypes(): array
    {
        return $this->ticketTypes->getValues();
    }

    /**
     * Sets type.
     *
     * @param TicketCategoryType $type
     *
     * @return $this
     */
    public function setType(TicketCategoryType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return TicketCategoryType
     */
    public function getType(): TicketCategoryType
    {
        return $this->type;
    }
}
