<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A Recurring email campaign.
 *
 * @ORM\Entity()
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"recurring_email_campaign_read"}},
 *     "denormalization_context"={"groups"={"recurring_email_campaign_write"}},
 *     "filters"={
 *         "recurring_email_campaign.search",
 *     },
 * })
 */
class RecurringEmailCampaign
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
     * @var string The expression logic to generate the source list.
     *
     * @ORM\Column(type="string", nullable=false)
     * @ApiProperty()
     */
    protected $sourceListGeneratorExpression;

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
     * @return string
     */
    public function getSourceListGeneratorExpression(): string
    {
        return $this->sourceListGeneratorExpression;
    }

    /**
     * Sets date.
     *
     * @param string $expression
     */
    public function setSourceListGeneratorExpression(string $expression): void
    {
        $this->sourceListGeneratorExpression = $expression;
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
