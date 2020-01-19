<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * The status history from billing web service.
 *
 * @ApiResource(iri="ApplicationRequestStatusHistory")
 */
class ApplicationRequestStatusHistory
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var \DateTime|null The date on which the CreativeWork was created or the item was added to a DataFeed.
     */
    protected $dateCreated;

    /**
     * @var string|null The textual content of this CreativeWork.
     */
    protected $text;

    public function __construct(?\DateTime $dateCreated, ?string $text)
    {
        $this->id = \uniqid();
        $this->dateCreated = $dateCreated;
        $this->text = $text;
    }

    /**
     * Gets id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

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
     * Gets text.
     *
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }
}
