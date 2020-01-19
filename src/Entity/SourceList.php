<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of campaign sources.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"source_list_read"}},
 *     "denormalization_context"={"groups"={"source_list_write"}},
 *     "filters"={
 *         "item_list.order",
 *         "item_list.search",
 *     },
 * })
 */
class SourceList extends ItemList
{
    /**
     * @var string|null The venn diagram formula used for generating the source list.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $vennDiagramFormula;

    /**
     * Sets vennDiagramFormula.
     *
     * @param string|null $vennDiagramFormula
     *
     * @return $this
     */
    public function setVennDiagramFormula(?string $vennDiagramFormula)
    {
        $this->vennDiagramFormula = $vennDiagramFormula;

        return $this;
    }

    /**
     * Gets vennDiagramFormula.
     *
     * @return string|null
     */
    public function getVennDiagramFormula(): ?string
    {
        return $this->vennDiagramFormula;
    }
}
