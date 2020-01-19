<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A mailgun deliver event.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"mailgun_deliver_event_read"}},
 *     "denormalization_context"={"groups"={"mailgun_deliver_event_write"}},
 *     "filters"={
 *         "mailgun_event.search",
 *         "mailgun_delivered_event.date",
 *     },
 * })
 */
class MailgunDeliverEvent extends MailgunEvent
{
    /**
     * @var \DateTime Delivery datetime.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $dateDelivered;

    /**
     * Gets dateDelivered.
     *
     * @return \DateTime
     */
    public function getDateDelivered(): \DateTime
    {
        return $this->dateDelivered;
    }

    /**
     * Sets dateDelivered.
     *
     * @param int $timestamp
     */
    public function setDateDelivered(int $timestamp): void
    {
        $temp = new \DateTime();
        $temp->setTimestamp($timestamp);
        $temp->setTimezone(new \DateTimeZone('UTC'));

        $this->dateDelivered = $temp;
    }
}
