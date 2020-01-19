<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\Priority;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The service level agreement.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"service_level_agreement_read"}},
 *     "denormalization_context"={"groups"={"service_level_agreement_write"}},
 *     "filters"={
 *         "service_level_agreement.order",
 *         "service_level_agreement.search",
 *     },
 * })
 */
class ServiceLevelAgreement
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
     * @var string A name of the item.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var Collection<OpeningHoursSpecification> The operations exclusions.
     *
     * @ORM\ManyToMany(targetEntity="OpeningHoursSpecification", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="service_level_agreement_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="opening_hours_specification_id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $operationExclusions;

    /**
     * @var Priority|null The priority.
     *
     * @ORM\Column(type="priority_enum", nullable=true)
     * @ApiProperty()
     */
    protected $priority;

    /**
     * @var Collection<TicketCategory> The ticket category.
     *
     * @ORM\ManyToMany(targetEntity="TicketCategory", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="service_level_agreement_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ticket_category_id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $ticketCategories;

    /**
     * @var Collection<TicketType> The ticket type.
     *
     * @ORM\ManyToMany(targetEntity="TicketType", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="service_level_agreement_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="ticket_type_id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     */
    protected $ticketTypes;

    /**
     * @var QuantitativeValue The timer value.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty()
     */
    protected $timer;

    public function __construct()
    {
        $this->operationExclusions = new ArrayCollection();
        $this->ticketCategories = new ArrayCollection();
        $this->ticketTypes = new ArrayCollection();
        $this->timer = new QuantitativeValue();
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
     * Adds operationExclusions.
     *
     * @param OpeningHoursSpecification $operationExclusions
     *
     * @return $this
     */
    public function addOperationExclusion(OpeningHoursSpecification $operationExclusions)
    {
        $this->operationExclusions[] = $operationExclusions;

        return $this;
    }

    /**
     * Removes operationExclusions.
     *
     * @param OpeningHoursSpecification $operationExclusions
     *
     * @return $this
     */
    public function removeOperationExclusion(OpeningHoursSpecification $operationExclusions)
    {
        $this->operationExclusions->removeElement($operationExclusions);

        return $this;
    }

    /**
     * Gets operationExclusions.
     *
     * @return OpeningHoursSpecification[]
     */
    public function getOperationExclusions(): array
    {
        return $this->operationExclusions->getValues();
    }

    /**
     * @param Priority|null $priority
     *
     * @return $this
     */
    public function setPriority(?Priority $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return Priority|null
     */
    public function getPriority(): ?Priority
    {
        return $this->priority;
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
     * Adds ticketTypes.
     *
     * @param TicketType $ticketTypes
     *
     * @return $this
     */
    public function addTicketType(TicketType $ticketTypes)
    {
        $this->ticketTypes[] = $ticketTypes;

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
     * Sets timer.
     *
     * @param QuantitativeValue $timer
     *
     * @return $this
     */
    public function setTimer(QuantitativeValue $timer)
    {
        $this->timer = $timer;

        return $this;
    }

    /**
     * Gets timer.
     *
     * @return QuantitativeValue
     */
    public function getTimer(): QuantitativeValue
    {
        return $this->timer;
    }
}
