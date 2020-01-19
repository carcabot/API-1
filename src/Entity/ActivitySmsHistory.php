<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Basically a join table for SmsHistory and Activity.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"activity_sms_history_read"}},
 *     "denormalization_context"={"groups"={"activity_sms_history_write"}},
 *     "filters"={
 *         "activity_sms_history.date",
 *         "activity_sms_history.order",
 *         "activity_sms_history.search",
 *     },
 * })
 */
class ActivitySmsHistory
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
     * @var Activity The activity carried out on an item.
     *
     * @ORM\ManyToOne(targetEntity="Activity")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $activity;

    /**
     * @var SmsHistory|null The inbound sms to the system.
     *
     * @ORM\ManyToOne(targetEntity="SmsHistory")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $inboundSMS;

    /**
     * @var SmsHistory The outbound sms from the system.
     *
     * @ORM\ManyToOne(targetEntity="SmsHistory")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $outboundSMS;

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
     * Sets activity.
     *
     * @param Activity $activity
     *
     * @return $this
     */
    public function setActivity(Activity $activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Gets activity.
     *
     * @return Activity
     */
    public function getActivity(): Activity
    {
        return $this->activity;
    }

    /**
     * Sets inboundSMS.
     *
     * @param SmsHistory|null $inboundSMS
     *
     * @return $this
     */
    public function setInboundSMS(?SmsHistory $inboundSMS)
    {
        $this->inboundSMS = $inboundSMS;

        return $this;
    }

    /**
     * Gets inboundSMS.
     *
     * @return SmsHistory|null
     */
    public function getInboundSMS(): ?SmsHistory
    {
        return $this->inboundSMS;
    }

    /**
     * Sets outboundSMS.
     *
     * @param SmsHistory $outboundSMS
     *
     * @return $this
     */
    public function setOutboundSMS(SmsHistory $outboundSMS)
    {
        $this->outboundSMS = $outboundSMS;

        return $this;
    }

    /**
     * Gets outboundSMS.
     *
     * @return SmsHistory
     */
    public function getOutboundSMS(): SmsHistory
    {
        return $this->outboundSMS;
    }
}
