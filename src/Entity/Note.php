<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\NoteType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A note attached to an item.
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"date_created"}),
 * })
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=128)
 * @ORM\DiscriminatorMap({
 *     "note"="Note",
 *     "quotation_note"="QuotationNote",
 * })
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"note_read"}},
 *     "denormalization_context"={"groups"={"note_write"}},
 *     "filters"={
 *         "note.search",
 *     },
 * })
 */
class Note
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
     * @var Collection<DigitalDocument> A CreativeWork attached to the message.
     *
     * @ORM\ManyToMany(targetEntity="DigitalDocument", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="note_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", onDelete="CASCADE")},
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty(iri="http://schema.org/messageAttachment")
     * @ApiSubresource()
     */
    protected $files;

    /**
     * @var string|null The textual content of this CreativeWork.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/text")
     */
    protected $text;

    /**
     * @var NoteType The note type.
     *
     * @ORM\Column(type="note_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    public function __construct()
    {
        $this->files = new ArrayCollection();
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
     * Adds file.
     *
     * @param DigitalDocument $file
     *
     * @return $this
     */
    public function addFile(DigitalDocument $file)
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * Removes file.
     *
     * @param DigitalDocument $file
     *
     * @return $this
     */
    public function removeFile(DigitalDocument $file)
    {
        $this->files->removeElement($file);

        return $this;
    }

    /**
     * Gets files.
     *
     * @return DigitalDocument[]
     */
    public function getFiles(): array
    {
        return $this->files->getValues();
    }

    /**
     * Sets text.
     *
     * @param string|null $text
     *
     * @return $this
     */
    public function setText(?string $text)
    {
        $this->text = $text;

        return $this;
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

    /**
     * Sets type.
     *
     * @param NoteType $type
     *
     * @return $this
     */
    public function setType(NoteType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return NoteType
     */
    public function getType(): NoteType
    {
        return $this->type;
    }
}
