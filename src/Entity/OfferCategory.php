<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The offer category.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"offer_category_read"}},
 *     "denormalization_context"={"groups"={"offer_category_write"}},
 *     "filters"={
 *         "offer_category.exists",
 *         "offer_category.order",
 *         "offer_category.search",
 *     },
 * })
 */
class OfferCategory
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
     * @var string The category number.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty()
     */
    protected $categoryNumber;

    /**
     * @var string|null Description of the credits category.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var DigitalDocument|null An image of the item.
     *
     * @ORM\OneToOne(targetEntity="DigitalDocument", orphanRemoval=true)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $image;

    /**
     * @var OfferCategory|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="OfferCategory")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var string The name of the credits configuration.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
            $this->isBasedOn = null;
            $this->image = null;
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
     * Sets categoryNumber.
     *
     * @param string $categoryNumber
     *
     * @return $this
     */
    public function setCategoryNumber(string $categoryNumber)
    {
        $this->categoryNumber = $categoryNumber;

        return $this;
    }

    /**
     * Gets categoryNumber.
     *
     * @return string
     */
    public function getCategoryNumber(): string
    {
        return $this->categoryNumber;
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
     * Gets isBasedOn.
     *
     * @return OfferCategory|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * Sets isBasedOn.
     *
     * @param OfferCategory|null $isBasedOn
     *
     * @return $this
     */
    public function setIsBasedOn(?self $isBasedOn)
    {
        $this->isBasedOn = $isBasedOn;

        return $this;
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
}
