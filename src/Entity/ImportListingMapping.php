<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\ImportListingTargetFields;
use Doctrine\ORM\Mapping as ORM;

/**
 * Relationship between the imported data and our database.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"import_listing_mapping_read"}},
 *     "denormalization_context"={"groups"={"import_listing_mapping_write"}},
 * })
 */
class ImportListingMapping
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string A Column from the imported Data
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty()
     */
    protected $source;

    /**
     * @var ImportListingTargetFields The field to join the source field with.
     *
     * @ORM\Column(type="import_listing_target_fields_enum", nullable=false)
     * @ApiProperty()
     */
    protected $target;

    /**
     * @var string The class to target either Lead or CustomerAccount
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty()
     */
    protected $targetClass;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return ImportListingTargetFields
     */
    public function getTarget(): ImportListingTargetFields
    {
        return $this->target;
    }

    /**
     * @param ImportListingTargetFields $target
     */
    public function setTarget(ImportListingTargetFields $target): void
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    /**
     * @param string $targetClass
     */
    public function setTargetClass(string $targetClass): void
    {
        $this->targetClass = $targetClass;
    }
}
