<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * A mailgun event.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *      "normalization_context"={"groups"={"mailgun_event_read"}},
 *      "denormalization_context"={"groups"={"mailgun_event_write"}},
 *      "filters"={
 *          "mailgun_event.search",
 *      },
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *      "mailgun_event"="MailgunEvent",
 *      "mailgun_bounce_event"="MailgunBounceEvent",
 *      "mailgun_click_event"="MailgunClickEvent",
 *      "mailgun_complain_event"="MailgunComplainEvent",
 *      "mailgun_deliver_event"="MailgunDeliverEvent",
 *      "mailgun_open_event"="MailgunOpenEvent",
 * })
 */
class MailgunEvent
{
    use TimestampableTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Campaign The campaign.
     *
     * @ORM\ManyToOne(targetEntity="Campaign")
     * @ORM\JoinColumn(nullable=false, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $campaign;

    /**
     * @var EmailCampaignSourceListItem The recipient of campaign's email.
     *
     * @ORM\ManyToOne(targetEntity="EmailCampaignSourceListItem")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $recipient;

    /**
     * @var string A unique id for event sent by mailgun.
     *
     * @ORM\Column(type="string", nullable=false)
     * @ApiProperty()
     */
    protected $mailgunEventId;

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets campaign.
     *
     * @return Campaign
     */
    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

    /**
     * Sets campaign.
     *
     * @param Campaign $campaign
     */
    public function setCampaign(Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    /**
     * Gets recipient.
     *
     * @return EmailCampaignSourceListItem
     */
    public function getRecipient(): EmailCampaignSourceListItem
    {
        return $this->recipient;
    }

    /**
     * Sets recipient.
     *
     * @param EmailCampaignSourceListItem $recipient
     */
    public function setRecipient(EmailCampaignSourceListItem $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * Gets mailgun event id.
     *
     * @return string
     */
    public function getMailgunEventId(): string
    {
        return $this->mailgunEventId;
    }

    /**
     * Sets mailgun event id.
     *
     * @param string $mailgunEventId
     */
    public function setMailgunEventId(string $mailgunEventId): void
    {
        $this->mailgunEventId = $mailgunEventId;
    }
}
