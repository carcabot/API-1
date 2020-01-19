<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\ORM\Mapping as ORM;

trait SourceableTrait
{
    /**
     * @var \App\Entity\CustomerAccount|null The organization or person from which the product was acquired.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty(iri="http://schema.org/acquiredFrom")
     */
    protected $acquiredFrom;

    /**
     * @var string|null The source of the item.
     *
     * @ORM\Column(type="source_enum", nullable=true)
     * @ApiProperty()
     */
    protected $source;

    /**
     * @var string|null The source url of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $sourceUrl;

    /**
     * Sets acquiredFrom.
     *
     * @param \App\Entity\CustomerAccount|null $acquiredFrom
     *
     * @return $this
     */
    public function setAcquiredFrom(?\App\Entity\CustomerAccount $acquiredFrom)
    {
        $this->acquiredFrom = $acquiredFrom;

        return $this;
    }

    /**
     * Gets acquiredFrom.
     *
     * @return \App\Entity\CustomerAccount|null
     */
    public function getAcquiredFrom(): ?\App\Entity\CustomerAccount
    {
        return $this->acquiredFrom;
    }

    /**
     * Sets source.
     *
     * @param string|null $source
     *
     * @return $this
     */
    public function setSource(?string $source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Gets source.
     *
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Sets sourceUrl.
     *
     * @param string|null $sourceUrl
     *
     * @return $this
     */
    public function setSourceUrl(?string $sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    /**
     * Gets sourceUrl.
     *
     * @return string|null
     */
    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }
}
