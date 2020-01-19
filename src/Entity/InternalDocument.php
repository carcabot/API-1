<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\DocumentType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * An internal document.
 *
 * @ORM\Entity
 * @Vich\Uploadable
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"internal_document_read"}},
 *     "denormalization_context"={"groups"={"internal_document_write"}},
 *     "filters"={
 *         "internal_document.date",
 *         "internal_document.order",
 *         "internal_document.search",
 *     },
 * })
 */
class InternalDocument
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
     * @var File|null
     *
     * @Vich\UploadableField(mapping="internal_file", fileNameProperty="contentPath")
     */
    protected $contentFile;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $contentPath;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var CustomerAccount|null The owner of the document.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     * @ApiProperty()
     */
    protected $owner;

    /**
     * @var string|null The textual content of this CreativeWork.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/text")
     */
    protected $text;

    /**
     * @var DocumentType The document type.
     *
     * @ORM\Column(type="document_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    /**
     * @var string|null URL of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/url")
     */
    protected $url;

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
     * Sets contentFile.
     *
     * @param File|null $contentFile
     *
     * @return $this
     */
    public function setContentFile(?File $contentFile)
    {
        $this->contentFile = $contentFile;

        return $this;
    }

    /**
     * Gets contentFile.
     *
     * @return File|null
     */
    public function getContentFile(): ?File
    {
        return $this->contentFile;
    }

    /**
     * Sets contentPath.
     *
     * @param string|null $contentPath
     *
     * @return $this
     */
    public function setContentPath(?string $contentPath)
    {
        $this->contentPath = $contentPath;

        return $this;
    }

    /**
     * Gets contentPath.
     *
     * @return string|null
     */
    public function getContentPath(): ?string
    {
        return $this->contentPath;
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
     * Sets owner.
     *
     * @param ?CustomerAccount $owner
     */
    public function setOwner(?CustomerAccount $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Gets owner.
     *
     * @return CustomerAccount|null
     */
    public function getOwner(): ?CustomerAccount
    {
        return $this->owner;
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
     * @param DocumentType $type
     *
     * @return $this
     */
    public function setType(DocumentType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return DocumentType
     */
    public function getType(): DocumentType
    {
        return $this->type;
    }

    /**
     * Sets url.
     *
     * @param string|null $url
     *
     * @return $this
     */
    public function setUrl(?string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Gets url.
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}
