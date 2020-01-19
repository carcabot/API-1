<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait TimestampableTrait
{
    /**
     * @var \DateTime|null The date on which the CreativeWork was created or the item was added to a DataFeed.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     * @ApiProperty(iri="http://schema.org/dateCreated")
     */
    protected $dateCreated;

    /**
     * @var \DateTime|null The date on which the CreativeWork was most recently modified or when the item's entry was modified within a DataFeed.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     * @ApiProperty(iri="http://schema.org/dateModified")
     */
    protected $dateModified;

    /**
     * Gets dateCreated.
     *
     * @return \DateTime|null
     */
    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    /**
     * Gets dateModified.
     *
     * @return \DateTime|null
     */
    public function getDateModified(): ?\DateTime
    {
        return $this->dateModified;
    }

    /**
     * @param \DateTime|null $dateCreated
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
    }

    /**
     * @param \DateTime|null $dateModified
     */
    public function setDateModified($dateModified)
    {
        $this->dateModified = $dateModified;
    }
}
