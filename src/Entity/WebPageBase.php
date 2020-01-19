<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use App\Enum\WebPageStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * A web page. Every web page is implicitly assumed to be declared to be of type WebPage, so the various properties about that webpage, such as breadcrumb may be used. We recommend explicit declaration if these properties are specified, but if they are found outside of an itemscope, they will be assumed to be about the page.
 *
 * @see http://schema.org/WebPage
 *
 * @ORM\MappedSuperclass
 */
class WebPageBase
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
     * @var DigitalDocument|null A cover image of the item.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $coverImage;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var string|null A sub property of description. A short description of the item used to disambiguate from other, similar items. Information from other properties (in particular, name) may be necessary for the description to be useful for disambiguation.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/disambiguatingDescription")
     */
    protected $disambiguatingDescription;

    /**
     * @var DigitalDocument|null An image of the item.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $image;

    /**
     * @var WebPageStatus The status of the web page.
     *
     * @ORM\Column(type="web_page_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

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
     * Sets coverImage.
     *
     * @param DigitalDocument|null $coverImage
     *
     * @return $this
     */
    public function setCoverImage(?DigitalDocument $coverImage)
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    /**
     * Gets coverImage.
     *
     * @return DigitalDocument|null
     */
    public function getCoverImage(): ?DigitalDocument
    {
        return $this->coverImage;
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
     * Sets disambiguatingDescription.
     *
     * @param string|null $disambiguatingDescription
     *
     * @return $this
     */
    public function setDisambiguatingDescription(?string $disambiguatingDescription)
    {
        $this->disambiguatingDescription = $disambiguatingDescription;

        return $this;
    }

    /**
     * Gets disambiguatingDescription.
     *
     * @return string|null
     */
    public function getDisambiguatingDescription(): ?string
    {
        return $this->disambiguatingDescription;
    }

    /**
     * Sets image.
     *
     * @param DigitalDocument|null $image
     *
     * @return $this
     */
    public function setImage(?DigitalDocument $image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Gets image.
     *
     * @return DigitalDocument|null
     */
    public function getImage(): ?DigitalDocument
    {
        return $this->image;
    }

    /**
     * Sets status.
     *
     * @param WebPageStatus $status
     *
     * @return $this
     */
    public function setStatus(WebPageStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return WebPageStatus
     */
    public function getStatus(): WebPageStatus
    {
        return $this->status;
    }
}
