<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The ticket management type.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"ticket_type_read"}},
 *     "denormalization_context"={"groups"={"ticket_type_write"}},
 *     "filters"={
 *         "ticket_type.json_search",
 *         "ticket_type.order",
 *         "ticket_type.search",
 *     },
 * })
 */
class TicketType
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
     * @var Collection<TicketCategory> A ticket category.
     *
     * @ORM\ManyToMany(targetEntity="TicketCategory", inversedBy="ticketTypes", cascade={"persist"})
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $ticketCategories;

    /**
     * @var string[] Where this type of ticket can be used.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $usedIn;

    public function __construct()
    {
        $this->ticketCategories = new ArrayCollection();
        $this->usedIn = [];
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
     * Adds ticketCategories.
     *
     * @param TicketCategory $ticketCategories
     *
     * @return $this
     */
    public function addTicketCategory(TicketCategory $ticketCategories)
    {
        $this->ticketCategories[] = $ticketCategories;

        return $this;
    }

    /**
     * Removes ticketCategories.
     *
     * @param TicketCategory $ticketCategories
     *
     * @return $this
     */
    public function removeTicketCategory(TicketCategory $ticketCategories)
    {
        $this->ticketCategories->removeElement($ticketCategories);

        return $this;
    }

    /**
     * Gets ticketCategories.
     *
     * @return TicketCategory[]
     */
    public function getTicketCategories(): array
    {
        return $this->ticketCategories->getValues();
    }

    /**
     * Adds usedIn.
     *
     * @param string $usedIn
     *
     * @return $this
     */
    public function addUsedIn(string $usedIn)
    {
        $this->usedIn[] = $usedIn;

        return $this;
    }

    /**
     * Removes usedIn.
     *
     * @param string $usedIn
     *
     * @return $this
     */
    public function removeUsedIn(string $usedIn)
    {
        if (false !== ($key = \array_search($usedIn, $this->usedIn, true))) {
            \array_splice($this->usedIn, $key, 1);
        }

        return $this;
    }

    /**
     * Gets usedIn.
     *
     * @return string[]
     */
    public function getUsedIn(): array
    {
        return $this->usedIn;
    }
}
