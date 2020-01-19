<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\SMSDirection;
use App\Enum\SMSWebServicePartner;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

/**
 * SMS history from/to the system.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"sms_history_read"}},
 *     "denormalization_context"={"groups"={"sms_history_write"}},
 * })
 */
class SmsHistory
{
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
     * @var \DateTime|null The date/time the message was received if a single recipient exists.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/dateReceived")
     */
    protected $dateReceived;

    /**
     * @var \DateTime|null The date/time at which the message was sent.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/dateSent")
     */
    protected $dateSent;

    /**
     * @var SMSDirection The sms direction.
     *
     * @ORM\Column(type="sms_direction_enum", nullable=false)
     * @ApiProperty()
     */
    protected $direction;

    /**
     * @var string|null The human readable message.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $message;

    /**
     * @var SMSWebServicePartner The service provider, service operator, or service performer; the goods producer. Another party (a seller) may offer those services or goods on behalf of the provider. A provider may also serve as the seller.
     *
     * @ORM\Column(type="sms_web_service_partner_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/provider")
     */
    protected $provider;

    /**
     * @var string|null The raw message.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $rawMessage;

    /**
     * @var PhoneNumber The recipient mobile phone number.
     *
     * @ORM\Column(type="phone_number", nullable=false)
     * @ApiProperty()
     */
    protected $recipientMobileNumber;

    /**
     * @var string|null The sender.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $sender;

    /**
     * @var PhoneNumber The recipient mobile phone number.
     *
     * @ORM\Column(type="phone_number", nullable=false)
     * @ApiProperty()
     */
    protected $senderMobileNumber;

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
     * Sets dateReceived.
     *
     * @param \DateTime|null $dateReceived
     *
     * @return $this
     */
    public function setDateReceived(?\DateTime $dateReceived)
    {
        $this->dateReceived = $dateReceived;

        return $this;
    }

    /**
     * Gets dateReceived.
     *
     * @return \DateTime|null
     */
    public function getDateReceived(): ?\DateTime
    {
        return $this->dateReceived;
    }

    /**
     * Sets dateSent.
     *
     * @param \DateTime|null $dateSent
     *
     * @return $this
     */
    public function setDateSent(?\DateTime $dateSent)
    {
        $this->dateSent = $dateSent;

        return $this;
    }

    /**
     * Gets dateSent.
     *
     * @return \DateTime|null
     */
    public function getDateSent(): ?\DateTime
    {
        return $this->dateSent;
    }

    /**
     * Sets direction.
     *
     * @param SMSDirection $direction
     *
     * @return $this
     */
    public function setDirection(SMSDirection $direction)
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * Gets direction.
     *
     * @return SMSDirection
     */
    public function getDirection(): SMSDirection
    {
        return $this->direction;
    }

    /**
     * Sets message.
     *
     * @param string|null $message
     *
     * @return $this
     */
    public function setMessage(?string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Gets message.
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Sets provider.
     *
     * @param SMSWebServicePartner $provider
     *
     * @return $this
     */
    public function setProvider(SMSWebServicePartner $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Gets provider.
     *
     * @return SMSWebServicePartner
     */
    public function getProvider(): SMSWebServicePartner
    {
        return $this->provider;
    }

    /**
     * Sets rawMessage.
     *
     * @param string|null $rawMessage
     *
     * @return $this
     */
    public function setRawMessage(?string $rawMessage)
    {
        $this->rawMessage = $rawMessage;

        return $this;
    }

    /**
     * Gets rawMessage.
     *
     * @return string|null
     */
    public function getRawMessage(): ?string
    {
        return $this->rawMessage;
    }

    /**
     * Sets recipientMobileNumber.
     *
     * @param PhoneNumber $recipientMobileNumber
     *
     * @return $this
     */
    public function setRecipientMobileNumber(PhoneNumber $recipientMobileNumber)
    {
        $this->recipientMobileNumber = $recipientMobileNumber;

        return $this;
    }

    /**
     * Gets recipientMobileNumber.
     *
     * @return PhoneNumber
     */
    public function getRecipientMobileNumber(): PhoneNumber
    {
        return $this->recipientMobileNumber;
    }

    /**
     * Sets sender.
     *
     * @param string|null $sender
     *
     * @return $this
     */
    public function setSender(?string $sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Gets sender.
     *
     * @return string|null
     */
    public function getSender(): ?string
    {
        return $this->sender;
    }

    /**
     * Sets senderMobileNumber.
     *
     * @param PhoneNumber $senderMobileNumber
     *
     * @return $this
     */
    public function setSenderMobileNumber(PhoneNumber $senderMobileNumber)
    {
        $this->senderMobileNumber = $senderMobileNumber;

        return $this;
    }

    /**
     * Gets senderMobileNumber.
     *
     * @return PhoneNumber
     */
    public function getSenderMobileNumber(): PhoneNumber
    {
        return $this->senderMobileNumber;
    }
}
