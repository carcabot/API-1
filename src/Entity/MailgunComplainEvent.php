<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A mailgun complain event.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"mailgun_complain_event_read"}},
 *     "denormalization_context"={"groups"={"mailgun_complain_event_write"}},
 *     "filters"={
 *         "mailgun_event.search",
 *         "mailgun_complain_event.date",
 *     },
 * })
 */
class MailgunComplainEvent extends MailgunEvent
{
    /**
     * @var \DateTime The date of complaining.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $dateComplained;

    /**
     * Gets dateComplained.
     *
     * @return \DateTime
     */
    public function getDateComplained(): \DateTime
    {
        return $this->dateComplained;
    }

    /**
     * Sets dateComplained.
     *
     * @param int $timestamp
     */
    public function setDateComplained(int $timestamp): void
    {
        $temp = new \DateTime();
        $temp->setTimestamp($timestamp);
        $temp->setTimezone(new \DateTimeZone('UTC'));

        $this->dateComplained = $temp;
    }
}
