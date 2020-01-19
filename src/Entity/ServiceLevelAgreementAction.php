<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\TicketStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * The act of resuming a service level agreement which was formerly paused.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"service_level_agreement_action_read"}},
 *     "denormalization_context"={"groups"={"service_level_agreement_action_write"}}
 * })
 */
class ServiceLevelAgreementAction
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
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var \DateTime|null The endTime of something. For a reserved event or service.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/endTime")
     */
    protected $endTime;

    /**
     * @var TicketStatus|null The previous action status.
     *
     * @ORM\Column(type="ticket_status_enum", nullable=true)
     * @ApiProperty()
     */
    protected $previousStatus;

    /**
     * @var TicketStatus The action status.
     *
     * @ORM\Column(type="ticket_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var \DateTime The startTime of something. For a reserved event or service.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty(iri="http://schema.org/startTime")
     */
    protected $startTime;

    /**
     * @var Ticket|null The ticket.
     *
     * @ORM\ManyToOne(targetEntity="Ticket", inversedBy="serviceLevelAgreementActions")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="https://schema.org/Ticket")
     */
    protected $ticket;

    /**
     * @var QuantitativeValue The value of the quantitative value or property value node.
     *
     * @ORM\Embedded(class="QuantitativeValue")
     * @ApiProperty(iri="http://schema.org/value")
     */
    protected $value;

    public function __construct()
    {
        $this->value = new QuantitativeValue();
    }

    /**
     * @return int|null
     */
    public function getId()
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
     * @return \DateTime
     */
    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }

    /**
     * @param \DateTime $startTime
     *
     * @return $this
     */
    public function setStartTime(\DateTime $startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param \DateTime $endTime
     *
     * @return $this
     */
    public function setEndTime(\DateTime $endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * @return Ticket|null
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * @param Ticket $ticket
     *
     * @return $this
     */
    public function setTicket(Ticket $ticket)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * @return QuantitativeValue
     */
    public function getValue(): QuantitativeValue
    {
        return $this->value;
    }

    /**
     * @param QuantitativeValue $value
     *
     * @return $this
     */
    public function setValue(QuantitativeValue $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return TicketStatus|null
     */
    public function getPreviousStatus(): ?TicketStatus
    {
        return $this->previousStatus;
    }

    /**
     * @param TicketStatus|null $previousStatus
     */
    public function setPreviousStatus(?TicketStatus $previousStatus)
    {
        $this->previousStatus = $previousStatus;
    }

    /**
     * Sets status.
     *
     * @param TicketStatus $status
     *
     * @return $this
     */
    public function setStatus(TicketStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return TicketStatus
     */
    public function getStatus(): TicketStatus
    {
        return $this->status;
    }
}
