<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A Country Calendar.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"country_calendar_read"}},
 *     "denormalization_context"={"groups"={"country_calendar_write"}}
 * })
 */
class CountryCalendar
{
    use Traits\BlameableTrait;
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
     * @var string the country identifier.
     *
     * @ORM\Column(type="string", nullable=false)
     * @ApiProperty()
     */
    protected $countryCode;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="string", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var bool Determines whether the calendar has been enabled
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @ApiProperty()
     */
    protected $enabled;

    /**
     * @var string The name of the item.
     *
     * @ORM\Column(type="string", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var Collection<OpeningHoursSpecification> The general opening hours for a business. Opening hours can be specified as a weekly time range, starting with days, then times per day. Multiple days can be listed with commas ',' separating each day. Day or time ranges are specified using a hyphen '-'.
     *
     * @ORM\ManyToMany(targetEntity="OpeningHoursSpecification", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="country_calendar_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="opening_hours_specification_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty(iri="http://schema.org/openingHours")
     * @ApiSubresource()
     */
    protected $openingHours;

    /**
     * @var \DateTime The date when the item becomes valid.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var \DateTime The date after when the item is not valid. For example the end of an offer, salary period, or a period of opening hours.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    public function __construct()
    {
        $this->openingHours = new ArrayCollection();
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
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Sets countryCode.
     *
     * @param string $countryCode
     *
     * @return $this
     */
    public function setCountryCode(string $countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Gets countryCode.
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Sets description.
     *
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
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
     * Adds openingHours.
     *
     * @param OpeningHoursSpecification $openingHours
     *
     * @return $this
     */
    public function addOpeningHour(OpeningHoursSpecification $openingHours)
    {
        $this->openingHours[] = $openingHours;

        return $this;
    }

    /**
     * Removes openingHours.
     *
     * @param OpeningHoursSpecification $openingHours
     *
     * @return $this
     */
    public function removeOpeningHour(OpeningHoursSpecification $openingHours)
    {
        $this->openingHours->removeElement($openingHours);

        return $this;
    }

    /**
     * Gets openingHours.
     *
     * @return OpeningHoursSpecification[]
     */
    public function getOpeningHours(): array
    {
        return $this->openingHours->getValues();
    }

    /**
     * Sets validFrom.
     *
     * @param \DateTime $validFrom
     *
     * @return $this
     */
    public function setValidFrom(\DateTime $validFrom)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * Gets validFrom.
     *
     * @return \DateTime
     */
    public function getValidFrom(): \DateTime
    {
        return $this->validFrom;
    }

    /**
     * Sets validThrough.
     *
     * @param \DateTime $validThrough
     *
     * @return $this
     */
    public function setValidThrough(\DateTime $validThrough)
    {
        $this->validThrough = $validThrough;

        return $this;
    }

    /**
     * Gets validThrough.
     *
     * @return \DateTime
     */
    public function getValidThrough(): \DateTime
    {
        return $this->validThrough;
    }
}
