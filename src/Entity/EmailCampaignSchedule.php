<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * An email campaign schedule.
 *
 * @ORM\Entity()
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"email_campaign_schedule_read"}},
 *     "denormalization_context"={"groups"={"email_campaign_schedule_write"}},
 *     "filters"={
 *         "email_campaign_schedule.search",
 *     },
 * })
 */
class EmailCampaignSchedule
{
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
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $campaign;

    /**
     * @var \DateTime The scheduled date.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $date;

    /**
     * @var int The start position of email campaign source list item to start sending from.
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ApiProperty()
     */
    protected $fromPosition;

    /**
     * @var int The start position of email campaign source list item to start sending from.
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ApiProperty()
     */
    protected $toPosition;

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
     * Gets date.
     *
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * Sets date.
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * Gets fromPosition.
     *
     * @return int
     */
    public function getFromPosition(): int
    {
        return $this->fromPosition;
    }

    /**
     * Sets fromPosition.
     *
     * @param int $fromPosition
     */
    public function setFromPosition(int $fromPosition): void
    {
        $this->fromPosition = $fromPosition;
    }

    /**
     * Gets toPosition.
     *
     * @return int
     */
    public function getToPosition(): int
    {
        return $this->toPosition;
    }

    /**
     * Sets toPosition.
     *
     * @param int $toPosition
     */
    public function setToPosition(int $toPosition): void
    {
        $this->toPosition = $toPosition;
    }
}
