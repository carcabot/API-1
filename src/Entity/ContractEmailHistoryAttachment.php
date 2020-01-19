<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * The email history attachment.
 *
 * @ApiResource(iri="ContractEmailHistoryAttachment", attributes={
 *     "normalization_context"={"groups"={"contract_email_history_attachment_read"}},
 * },
 * collectionOperations={
 *     "get",
 * },
 * itemOperations={
 *     "get",
 * })
 */
class ContractEmailHistoryAttachment
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var string The content type.
     */
    private $contentType;

    /**
     * @var string The filename.
     */
    private $filename;

    /**
     * @var int The file size.
     */
    private $filesize;

    /**
     * @var string|null The url.
     */
    private $url;

    /**
     * @param int         $id
     * @param string      $filename
     * @param string      $contentType
     * @param int         $filesize
     * @param string|null $url
     */
    public function __construct(int $id, ?string $filename, ?string $contentType, ?int $filesize, ?string $url = null)
    {
        $this->id = (string) $id;
        $this->filename = $filename;
        $this->filesize = $filesize;
        $this->contentType = $contentType;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return int
     */
    public function getFilesize(): int
    {
        return $this->filesize;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}
