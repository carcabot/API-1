<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * FAQ configuration.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"faq_configuration_read"}},
 *     "denormalization_context"={"groups"={"faq_configuration_write"}},
 *     "filters"={
 *         "faq_configuration.order",
 *     },
 * })
 */
class FaqConfiguration
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
     * @var FaqConfigurationCategory|null
     *
     * @ORM\ManyToOne(targetEntity="FaqConfigurationCategory")
     * @ORM\JoinColumn(nullable=true, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $category;

    /**
     * @var string The name of the item.
     *
     * @ORM\Column(type="string")
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var int|null The position of an item in a series or sequence of items.
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ApiProperty(iri="http://schema.org/position")
     */
    protected $position;

    /**
     * @var string The textual content of this CreativeWork.
     *
     * @ORM\Column(type="text")
     * @ApiProperty(iri="http://schema.org/text")
     */
    protected $text;

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
     * @return FaqConfigurationCategory|null
     */
    public function getCategory(): ?FaqConfigurationCategory
    {
        return $this->category;
    }

    /**
     * @param FaqConfigurationCategory|null $category
     */
    public function setCategory(?FaqConfigurationCategory $category): void
    {
        $this->category = $category;
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
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets position.
     *
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Sets position.
     *
     * @param int|null $position
     *
     * @return $this
     */
    public function setPosition(?int $position)
    {
        $this->position = $position;

        return $this;
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
     *
     * @return $this
     */
    public function setText(string $text)
    {
        $this->text = $text;

        return $this;
    }
}
