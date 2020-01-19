<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\OfferCatalogExportController;
use App\Enum\CatalogStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * The offer catalog.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"offer_catalog_read"}},
 *     "denormalization_context"={"groups"={"offer_catalog_write"}},
 *     "filters"={
 *         "offer_catalog.date",
 *         "offer_catalog.order",
 *         "offer_catalog.search",
 *     },
 * },
 * itemOperations={
 *     "delete",
 *     "get",
 *     "export"={
 *          "method"="GET",
 *          "path"="/offer_catalogs/{id}/export",
 *          "controller"=OfferCatalogExportController::class
 *     },
 *     "put"
 * })
 */
class OfferCatalog extends ItemList
{
    /**
     * @var DigitalDocument|null A cover image of the item.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $coverImage;

    /**
     * @var DigitalDocument|null An image of the item.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $image;

    /**
     * @var string|null The remarks for offer catalog
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $remarks;

    /**
     * @var CatalogStatus The offer catalog status
     *
     * @ORM\Column(type="catalog_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var \DateTime|null The creation date of offer catalog.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var \DateTime|null The final date of offer catalog.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    /**
     * Gets cover image.
     *
     * @return DigitalDocument|null
     */
    public function getCoverImage(): ?DigitalDocument
    {
        return $this->coverImage;
    }

    /**
     * Sets cover image.
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
     * Gets image.
     *
     * @return DigitalDocument|null
     */
    public function getImage(): ?DigitalDocument
    {
        return $this->image;
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
     * Sets remarks.
     *
     * @param string|null $remarks
     *
     * @return $this
     */
    public function setRemarks(?string $remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Gets remarks.
     *
     * @return string|null
     */
    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    /**
     * Sets status.
     *
     * @param CatalogStatus $status
     *
     * @return $this
     */
    public function setStatus(CatalogStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return CatalogStatus
     */
    public function getStatus(): CatalogStatus
    {
        return $this->status;
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
