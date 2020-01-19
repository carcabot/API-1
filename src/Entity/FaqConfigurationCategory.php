<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * FAQ configuration.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"faq_configuration_category_read"}},
 *     "denormalization_context"={"groups"={"faq_configuration_category_write"}},
 *     "filters"={
 *         "faq_configuration_category.boolean",
 *         "faq_configuration_category.order",
 *     },
 * })
 */
class FaqConfigurationCategory
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
     * @var bool The enabled indicator.
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @ApiProperty()
     */
    protected $enabled;

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
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
