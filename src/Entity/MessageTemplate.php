<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\MessageStatus;
use App\Enum\MessageType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A single message from a sender to one or more organizations or people.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string")
 * @ORM\DiscriminatorMap({"message_template"="MessageTemplate", "message" = "Message"})
 * @ApiResource(attributes={
 *          "normalization_context"={"groups"={"message_template_read"}},
 *          "denormalization_context"={"groups"={"message_template_write"}},
 *          "filters"={
 *          "message_template.order",
 *          "message_template.search"
 *          },
 *      },
 * )
 */
class MessageTemplate
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
     * @var string
     *
     * @ORM\Column(type="string")
     * @ApiProperty()
     */
    protected $body;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var \DateTime|null The end date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/endDate")
     */
    protected $endDate;

    /**
     * @var Collection<DigitalDocument>
     *
     * @ORM\ManyToMany(targetEntity="DigitalDocument", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="digital_document_id", referencedColumnName="id", unique=true, onDelete="CASCADE")},
     * )
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $messageAttachments;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true)
     * @ApiProperty()
     */
    protected $messageNumber;

    /**
     * @var \DateTime|null Planned end date of notification.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    protected $plannedEndDate;

    /**
     * @var \DateTime Planned start date of notification.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $plannedStartDate;

    /**
     * @var Collection<MessageRecipientListItem>
     *
     * @ORM\ManyToMany(targetEntity="MessageRecipientListItem", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="recipient_list_item_id", referencedColumnName="id", unique=true, onDelete="CASCADE")},
     * )
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $recipients;

    /**
     * @ORM\Column(type="json", nullable=true, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $recipientsFilters;

    /**
     * @var \DateTime|null The start date and time of the item (in ISO 8601 date format).
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/startDate")
     */
    protected $startDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @ApiProperty()
     */
    protected $title;

    /**
     * @var MessageStatus The type of notification.
     *
     * @ORM\Column(type="message_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var MessageType The type of notification.
     *
     * @ORM\Column(type="message_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * MessageTemplate constructor.
     */
    public function __construct()
    {
        $this->messageAttachments = new ArrayCollection();
        $this->recipients = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
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
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime|null $endDate
     */
    public function setEndDate(?\DateTime $endDate): void
    {
        $this->endDate = $endDate;
    }

    /**
     * @return DigitalDocument[]
     */
    public function getMessageAttachments(): array
    {
        return $this->messageAttachments->getValues();
    }

    /**
     * @param DigitalDocument $messageAttachment
     *
     * @return MessageTemplate
     */
    public function addMessageAttachments(DigitalDocument $messageAttachment)
    {
        $this->messageAttachments[] = $messageAttachment;

        return $this;
    }

    /**
     * @param DigitalDocument $messageAttachment
     *
     * @return MessageTemplate
     */
    public function removeMessageAttachment(DigitalDocument $messageAttachment)
    {
        $this->messageAttachments->removeElement($messageAttachment);

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageNumber(): string
    {
        return $this->messageNumber;
    }

    /**
     * @param string $messageNumber
     */
    public function setMessageNumber(string $messageNumber): void
    {
        $this->messageNumber = $messageNumber;
    }

    /**
     * @return \DateTime|null
     */
    public function getPlannedEndDate(): ?\DateTime
    {
        return $this->plannedEndDate;
    }

    /**
     * @param \DateTime|null $plannedEndDate
     */
    public function setPlannedEndDate(?\DateTime $plannedEndDate): void
    {
        $this->plannedEndDate = $plannedEndDate;
    }

    /**
     * @return \DateTime
     */
    public function getPlannedStartDate(): \DateTime
    {
        return $this->plannedStartDate;
    }

    /**
     * @param \DateTime $plannedStartDate
     */
    public function setPlannedStartDate(\DateTime $plannedStartDate): void
    {
        $this->plannedStartDate = $plannedStartDate;
    }

    /**
     * @return MessageRecipientListItem[]
     */
    public function getRecipients(): array
    {
        return $this->recipients->getValues();
    }

    /**
     * @param MessageRecipientListItem $recipient
     */
    public function addRecipient(MessageRecipientListItem $recipient): void
    {
        $this->recipients[] = $recipient;
    }

    /**
     * @param MessageRecipientListItem $recipient
     *
     * @return MessageTemplate
     */
    public function removeRecipient(MessageRecipientListItem $recipient)
    {
        $this->recipients->removeElement($recipient);

        return $this;
    }

    /**
     * @return $this
     */
    public function clearMessageRecipients()
    {
        $this->recipients = new ArrayCollection();

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecipientsFilters()
    {
        return $this->recipientsFilters;
    }

    /**
     * @param mixed $recipientsFilters
     */
    public function setRecipientsFilters($recipientsFilters): void
    {
        $this->recipientsFilters = $recipientsFilters;
    }

    /**
     * @return \DateTime|null
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime|null $startDate
     */
    public function setStartDate(?\DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    /**
     * @return MessageStatus
     */
    public function getStatus(): MessageStatus
    {
        return $this->status;
    }

    /**
     * @param MessageStatus $status
     */
    public function setStatus(MessageStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return MessageType
     */
    public function getType(): MessageType
    {
        return $this->type;
    }

    /**
     * @param MessageType $type
     */
    public function setType(MessageType $type): void
    {
        $this->type = $type;
    }
}
