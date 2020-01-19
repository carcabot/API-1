<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Traits\BlameableTrait;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * A campaign template.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"campaign_template_read"}},
 *     "denormalization_context"={"groups"={"campaign_template_write"}},
 *     "filters"={
 *         "campaign_template.exists",
 *         "campaign_template.order",
 *         "campaign_template.search",
 *     },
 * })
 */
class CampaignTemplate
{
    use BlameableTrait;
    use TimestampableTrait;
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var CampaignTemplate|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="CampaignTemplate")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var string The name of the item.
     *
     * @ORM\Column(type="string")
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var string The raw textual content of this CreativeWork.
     *
     * @ORM\Column(type="text")
     * @ApiProperty()
     */
    protected $rawText;

    /**
     * @var string The textual content of this CreativeWork.
     *
     * @ORM\Column(type="text")
     * @ApiProperty(iri="http://schema.org/text")
     */
    protected $text;

    /**
     * @var DigitalDocument|null An electronic file or document.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $thumbnailImage;

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
     * Sets isBasedOn.
     *
     * @param CampaignTemplate|null $isBasedOn
     *
     * @return $this
     */
    public function setIsBasedOn(?self $isBasedOn)
    {
        $this->isBasedOn = $isBasedOn;

        return $this;
    }

    /**
     * Gets isBasedOn.
     *
     * @return CampaignTemplate|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
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
     * Sets name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets rawText.
     *
     * @return string
     */
    public function getRawText(): string
    {
        return $this->rawText;
    }

    /**
     * Sets rawText.
     *
     * @param string $rawText
     */
    public function setRawText(string $rawText): void
    {
        $this->rawText = $rawText;
    }

    /**
     * Gets text.
     *
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Sets text.
     *
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * Sets thumbnailImage.
     *
     * @param DigitalDocument|null $thumbnailImage
     *
     * @return $this
     */
    public function setThumbnailImage(?DigitalDocument $thumbnailImage)
    {
        $this->thumbnailImage = $thumbnailImage;

        return $this;
    }

    /**
     * Gets thumbnailImage.
     *
     * @return DigitalDocument|null
     */
    public function getThumbnailImage(): ?DigitalDocument
    {
        return $this->thumbnailImage;
    }
}
