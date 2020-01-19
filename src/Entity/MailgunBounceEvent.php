<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A mailgun bounce event.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"mailgun_bounce_event_read"}},
 *     "denormalization_context"={"groups"={"mailgun_bounce_event_write"}},
 *     "filters"={
 *         "mailgun_event.search",
 *         "mailgun_bounced_event.date",
 *     },
 * })
 */
class MailgunBounceEvent extends MailgunEvent
{
    /**
     * @var \DateTime Bounce datetime.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $dateBounced;

    /**
     * Gets date bounce.
     *
     * @return \DateTime
     */
    public function getDateBounced(): \DateTime
    {
        return $this->dateBounced;
    }

    /**
     * Sets date bounce.
     *
     * @param int $timestamp
     */
    public function setDateBounced(int $timestamp): void
    {
        $temp = new \DateTime();
        $temp->setTimestamp($timestamp);
        $temp->setTimezone(new \DateTimeZone('UTC'));

        $this->dateBounced = $temp;
    }
}
