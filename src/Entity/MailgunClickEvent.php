<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A mailgun click event.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"mailgun_click_event_read"}},
 *     "denormalization_context"={"groups"={"mailgun_click_event_write"}},
 *     "filters"={
 *         "mailgun_event.date",
 *         "mailgun_event.search",
 *         "mailgun_click_event.json",
 *     },
 * })
 */
class MailgunClickEvent extends MailgunEvent
{
    /**
     * @var string The clicked url;
     *
     * @ORM\Column(type="string", nullable=false)
     * @ApiProperty()
     */
    protected $url;

    /**
     * @var \DateTime[] The dates of clicks.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $datesClicked;

    public function __construct()
    {
        $this->datesClicked = [];
    }

    /**
     * Gets url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Sets url.
     *
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Gets datesClicked.
     *
     * @return \DateTime[]
     */
    public function getDatesClicked(): array
    {
        return $this->datesClicked;
    }

    /**
     * Add dateClicked.
     *
     * @param int $timestamp
     */
    public function addDateClicked(int $timestamp)
    {
        $temp = new \DateTime();
        $temp->setTimestamp($timestamp);
        $temp->setTimezone(new \DateTimeZone('UTC'));

        $this->datesClicked[] = $temp;
    }

    /**
     * Remove dateClicked.
     *
     * @param \DateTime $dateClicked
     */
    public function removeDateClicked(\DateTime $dateClicked)
    {
        if (false !== ($key = \array_search($dateClicked, $this->datesClicked, true))) {
            \array_splice($this->datesClicked, $key);
        }
    }
}
