<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The ticket service level agreement.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"ticket_service_level_agreement_read"}},
 *     "denormalization_context"={"groups"={"ticket_service_level_agreement_write"}},
 * })
 */
class TicketServiceLevelAgreement
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
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var ServiceLevelAgreement|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="ServiceLevelAgreement")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

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
     *     joinColumns={@ORM\JoinColumn(name="ticket_service_level_agreement_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="opening_hours_specification_id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $operationExclusions;

    /**
     * @var Ticket The ticket.
     *
     * @ORM\OneToOne(targetEntity="Ticket", mappedBy="serviceLevelAgreement", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @ApiProperty()
     */
    protected $ticket;

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
     * Sets isBasedOn.
     *
     * @param ServiceLevelAgreement|null $isBasedOn
     *
     * @return $this
     */
    public function setIsBasedOn(?ServiceLevelAgreement $isBasedOn)
    {
        $this->isBasedOn = $isBasedOn;

        return $this;
    }

    /**
     * Gets isBasedOn.
     *
     * @return ServiceLevelAgreement|null
     */
    public function getIsBasedOn(): ?ServiceLevelAgreement
    {
        return $this->isBasedOn;
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
     * Sets ticket.
     *
     * @param Ticket $ticket
     *
     * @return $this
     */
    public function setTicket(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $ticket->setServiceLevelAgreement($this);

        return $this;
    }

    /**
     * Gets ticket.
     *
     * @return Ticket
     */
    public function getTicket(): Ticket
    {
        return $this->ticket;
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
