<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\DayOfWeek;
use Doctrine\ORM\Mapping as ORM;

/**
 * OpeningHoursSpecification. A structured value providing information about the opening hours of a place or a certain service inside a place.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"opening_hours_specification_read"}},
 *     "denormalization_context"={"groups"={"opening_hours_specification_write"}},
 *
 * })
 */
class OpeningHoursSpecification
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime|null The closing hour of the place or service on the given day(s) of the week.
     *
     * @ORM\Column(type="time", nullable=true)
     * @ApiProperty(iri="http://schema.org/closes")
     */
    protected $closes;

    /**
     * @var DayOfWeek The day of the week for which these opening hours are valid.
     *
     * @ORM\Column(type="day_of_week_enum", nullable=false)
     * @ApiProperty(iri="http://schema.org/dayOfWeek")
     */
    protected $dayOfWeek;

    /**
     * @var string The name of the item.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var \DateTime|null The opening hour of the place or service on the given day(s) of the week.
     *
     * @ORM\Column(type="time", nullable=true)
     * @ApiProperty(iri="http://schema.org/opens")
     */
    protected $opens;

    /**
     * @var \DateTime|null The date when the item becomes valid.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var \DateTime|null The date after when the item is not valid. For example the end of an offer, salary period, or a period of opening hours.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;

            if (null !== $this->closes) {
                $this->closes = clone $this->closes;
            }

            if (null !== $this->opens) {
                $this->opens = clone $this->opens;
            }

            if (null !== $this->validFrom) {
                $this->validFrom = clone $this->validFrom;
            }

            if (null !== $this->validThrough) {
                $this->validThrough = clone $this->validThrough;
            }
        }
    }

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
     * Sets closes.
     *
     * @param \DateTime|null $closes
     *
     * @return $this
     */
    public function setCloses(?\DateTime $closes)
    {
        $this->closes = $closes;

        return $this;
    }

    /**
     * Gets closes.
     *
     * @return \DateTime|null
     */
    public function getCloses(): ?\DateTime
    {
        return $this->closes;
    }

    /**
     * Sets dayOfWeek.
     *
     * @param DayOfWeek $dayOfWeek
     *
     * @return $this
     */
    public function setDayOfWeek(DayOfWeek $dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    /**
     * Gets dayOfWeek.
     *
     * @return DayOfWeek
     */
    public function getDayOfWeek(): DayOfWeek
    {
        return $this->dayOfWeek;
    }

    /**
     * Sets name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets opens.
     *
     * @param \DateTime|null $opens
     *
     * @return $this
     */
    public function setOpens(?\DateTime $opens)
    {
        $this->opens = $opens;

        return $this;
    }

    /**
     * Gets opens.
     *
     * @return \DateTime|null
     */
    public function getOpens(): ?\DateTime
    {
        return $this->opens;
    }

    /**
     * Sets validFrom.
     *
     * @param \DateTime|null $validFrom
     *
     * @return $this
     */
    public function setValidFrom(?\DateTime $validFrom)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * Gets validFrom.
     *
     * @return \DateTime|null
     */
    public function getValidFrom(): ?\DateTime
    {
        return $this->validFrom;
    }

    /**
     * Sets validThrough.
     *
     * @param \DateTime|null $validThrough
     *
     * @return $this
     */
    public function setValidThrough(?\DateTime $validThrough)
    {
        $this->validThrough = $validThrough;

        return $this;
    }

    /**
     * Gets validThrough.
     *
     * @return \DateTime|null
     */
    public function getValidThrough(): ?\DateTime
    {
        return $this->validThrough;
    }
}
