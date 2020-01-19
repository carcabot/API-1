<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\UnsubscribeReasonStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * A unsubscribe reason.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"unsubscribe_reason_read"}},
 *     "denormalization_context"={"groups"={"unsubscribe_reason_write"}},
 * })
 */
class UnsubscribeReason
{
    use Traits\BlameableTrait;
    use Traits\TimestampableTrait;

    /**
     * @var int
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
     * @var string The name of the item.
     *
     * @ORM\Column(type="string")
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var bool Indicates if unsubscribe reason need additional info.
     *
     * @ORM\Column(type="boolean")
     * @ApiProperty()
     */
    protected $requireNote;

    /**
     * @var UnsubscribeReasonStatus Unsubscribe reason status.
     *
     * @ORM\Column(type="unsubscribe_reason_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * Sets description.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
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
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets isRequireNote.
     *
     * @return bool
     */
    public function isRequireNote(): bool
    {
        return $this->requireNote;
    }

    /**
     * Sets isRequireNote.
     *
     * @param bool $requireNote
     */
    public function setRequireNote(bool $requireNote): void
    {
        $this->requireNote = $requireNote;
    }

    /**
     * Gets UnsubscribeReasonStatus.
     *
     * @return UnsubscribeReasonStatus
     */
    public function getStatus(): UnsubscribeReasonStatus
    {
        return $this->status;
    }

    /**
     * Sets UnsubscribeReasonStatus.
     *
     * @param UnsubscribeReasonStatus $status
     */
    public function setStatus(UnsubscribeReasonStatus $status): void
    {
        $this->status = $status;
    }
}
