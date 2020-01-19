<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\MessageStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * A message from a sender sent to one or more organizations or people.
 *
 * @see http://schema.org/Message
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Message",
 *      attributes={
 *          "normalization_context"={"groups"={"message_read"}},
 *          "denormalization_context"={"groups"={"message_write"}},
 *          "filters"={
 *              "message.date",
 *              "message.search",
 *              "message.order",
 *          }
 *      },
 * )
 */
class Message
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
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    private $dateRead;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    private $dateReceived;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty()
     */
    private $dateSent;

    /**
     * @var MessageTemplate
     *
     * @ORM\ManyToOne(targetEntity="MessageTemplate")
     * @ApiProperty()
     */
    protected $messageTemplate;

    /**
     * @var MessageRecipientListItem
     *
     * @ORM\OneToOne(targetEntity="MessageRecipientListItem", mappedBy="message")
     */
    private $recipient;

    /**
     * @var array|null
     */
    private $responseDetails;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty()
     */
    private $responseMessage;

    /**
     * @var MessageStatus Status of notification.
     *
     * @ORM\Column(type="message_status_enum", nullable=true)
     * @ApiProperty()
     */
    protected $status;

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
     * @return \DateTime|null
     */
    public function getDateRead(): ?\DateTime
    {
        return $this->dateRead;
    }

    /**
     * @param \DateTime|null $dateRead
     */
    public function setDateRead(?\DateTime $dateRead): void
    {
        $this->dateRead = $dateRead;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateReceived(): ?\DateTime
    {
        return $this->dateReceived;
    }

    /**
     * @param \DateTime|null $dateReceived
     */
    public function setDateReceived(?\DateTime $dateReceived): void
    {
        $this->dateReceived = $dateReceived;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateSent(): ?\DateTime
    {
        return $this->dateSent;
    }

    /**
     * @param \DateTime|null $dateSent
     */
    public function setDateSent(?\DateTime $dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return MessageTemplate
     */
    public function getMessageTemplate(): MessageTemplate
    {
        return $this->messageTemplate;
    }

    /**
     * @param MessageTemplate $messageTemplate
     */
    public function setMessageTemplate(MessageTemplate $messageTemplate): void
    {
        $this->messageTemplate = $messageTemplate;
    }

    /**
     * @return MessageRecipientListItem
     */
    public function getRecipient(): MessageRecipientListItem
    {
        return $this->recipient;
    }

    /**
     * @param MessageRecipientListItem $recipient
     */
    public function setRecipient(MessageRecipientListItem $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * @return array|null
     */
    public function getResponseDetails(): ?array
    {
        return $this->responseDetails;
    }

    /**
     * @param array|null $responseDetails
     */
    public function setResponseDetails(?array $responseDetails): void
    {
        $this->responseDetails = $responseDetails;
    }

    /**
     * @return string|null
     */
    public function getResponseMessage(): ?string
    {
        return $this->responseMessage;
    }

    /**
     * @param string|null $responseMessage
     */
    public function setResponseMessage(?string $responseMessage): void
    {
        $this->responseMessage = $responseMessage;
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
}
