<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A free gift.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"free_gift_read"}},
 *     "denormalization_context"={"groups"={"free_gift_write"}},
 * })
 */
class FreeGift
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
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var FreeGift|null A resource that was used in the creation of this resource. This term can be repeated for multiple sources. For example, http://example.com/great-multiplication-intro.html.
     *
     * @ORM\ManyToOne(targetEntity="FreeGift")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/isBasedOn")
     */
    protected $isBasedOn;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var string|null Add on service note.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $note;

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;
            $this->isBasedOn = null;
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
     * Sets isBasedOn.
     *
     * @param FreeGift|null $isBasedOn
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
     * @return FreeGift|null
     */
    public function getIsBasedOn(): ?self
    {
        return $this->isBasedOn;
    }

    /**
     * Sets name.
     *
     * @param string|null $name
     *
     * @return $this
     */
    public function setName(?string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets note.
     *
     * @param string|null $note
     *
     * @return $this
     */
    public function setNote(?string $note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Gets note.
     *
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }
}
