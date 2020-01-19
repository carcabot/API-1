<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\WebService\Billing\Controller\ContractWelcomePackageAttachmentController;
use App\WebService\Billing\Controller\ContractWelcomePackageController;

/**
 * The contract welcome package.
 *
 * @ApiResource(iri="ContractWelcomePackage", attributes={
 *     "normalization_context"={"groups"={"contract_welcome_package_attachment_read", "contract_welcome_package_read"}},
 * },
 * collectionOperations={
 *     "get",
 *     "get_welcome_package"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/welcome_packages.{_format}",
 *         "controller"=ContractWelcomePackageController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"contract_welcome_package_read"}},
 *     },
 * },
 * itemOperations={
 *     "get",
 *     "get_welcome_package_attachment"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/welcome_packages/{fileKey}.{_format}",
 *         "controller"=ContractWelcomePackageAttachmentController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"contract_welcome_package_attachment_read"}},
 *     },
 * })
 */
class ContractWelcomePackage
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var string|null The content type.
     */
    private $contentType;

    /**
     * @var \DateTime|null The created date.
     */
    private $dateCreated;

    /**
     * @var string|null The filename.
     */
    private $filename;

    /**
     * @var int|null The file size.
     */
    private $filesize;

    /**
     * @var string|null The url.
     */
    private $url;

    /**
     * @param int            $id
     * @param string|null    $filename
     * @param string|null    $contentType
     * @param int|null       $filesize
     * @param \DateTime|null $dateCreated
     * @param string|null    $url
     */
    public function __construct(int $id, ?string $filename, ?string $contentType, ?int $filesize, ?\DateTime $dateCreated, ?string $url = null)
    {
        $this->id = (string) $id;
        $this->filename = $filename;
        $this->filesize = $filesize;
        $this->contentType = $contentType;
        $this->dateCreated = $dateCreated;
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
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @return int|null
     */
    public function getFilesize(): ?int
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
