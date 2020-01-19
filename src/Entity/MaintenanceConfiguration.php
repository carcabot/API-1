<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\MaintenanceConfigurationStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * The details for maintenance configuration page.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *          "normalization_context"={"groups"={"maintenance_configuration_read"}},
 *          "denormalization_context"={"groups"={"maintenance_configuration_write"}},
 *      }
 * )
 */
class MaintenanceConfiguration
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
     * @var string The subject matter of the content.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/about")
     */
    protected $about;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var \DateTime Planned end date of maintenance.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $plannedEndDate;

    /**
     * @var \DateTime Planned start date of maintenance.
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @ApiProperty()
     */
    protected $plannedStartDate;

    /**
     * @var MaintenanceConfigurationStatus Status of notification.
     *
     * @ORM\Column(type="maintenance_configuration_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var string The textual content of this CreativeWork.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/text")
     */
    protected $text;

    /**
     * @var string[] Where the maintenance will be displayed.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    protected $usedIn;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAbout(): string
    {
        return $this->about;
    }

    /**
     * @param string $about
     */
    public function setAbout(string $about): void
    {
        $this->about = $about;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return \DateTime
     */
    public function getPlannedEndDate(): \DateTime
    {
        return $this->plannedEndDate;
    }

    /**
     * @param \DateTime $plannedEndDate
     */
    public function setPlannedEndDate(\DateTime $plannedEndDate): void
    {
        $this->plannedEndDate = $plannedEndDate;
    }

    /**
     * @return \DateTime
     */
    public function getPlannedStartDate(): \DateTime
    {
        return $this->plannedStartDate;
    }

    /**
     * @param \DateTime $plannedStartDate
     */
    public function setPlannedStartDate(\DateTime $plannedStartDate): void
    {
        $this->plannedStartDate = $plannedStartDate;
    }

    /**
     * @return MaintenanceConfigurationStatus
     */
    public function getStatus(): MaintenanceConfigurationStatus
    {
        return $this->status;
    }

    /**
     * @param MaintenanceConfigurationStatus $status
     */
    public function setStatus(MaintenanceConfigurationStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return string[]
     */
    public function getUsedIn(): array
    {
        return $this->usedIn;
    }

    /**
     * @param string[] $usedIn
     */
    public function setUsedIn(array $usedIn): void
    {
        $this->usedIn = $usedIn;
    }
}
