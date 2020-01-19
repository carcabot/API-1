<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A mailgun open event.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"mailgun_open_event_read"}},
 *     "denormalization_context"={"groups"={"mailgun_open_event_write"}},
 *     "filters"={
 *         "mailgun_event.date",
 *         "mailgun_event.search",
 *         "mailgun_open_event.json",
 *     },
 * })
 */
class MailgunOpenEvent extends MailgunEvent
{
    /**
     * @var \DateTime[] The dates of opens.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $datesOpened;

    public function __construct()
    {
        $this->datesOpened = [];
    }

    /**
     * Gets datesOpened.
     *
     * @return \DateTime[]
     */
    public function getDatesOpened(): array
    {
        return $this->datesOpened;
    }

    /**
     * Add dateOpen.
     *
     * @param int $timestamp
     */
    public function addDateOpened(int $timestamp)
    {
        $temp = new \DateTime();
        $temp->setTimestamp($timestamp);
        $temp->setTimezone(new \DateTimeZone('UTC'));

        $this->datesOpened[] = $temp;
    }

    /**
     * Remove dateOpened.
     *
     * @param \DateTime $dateOpened
     */
    public function removeDateOpen(\DateTime $dateOpened)
    {
        if (false !== ($key = \array_search($dateOpened, $this->datesOpened, true))) {
            \array_splice($this->datesOpened, $key);
        }
    }
}
