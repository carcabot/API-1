<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\WebService\Billing\Controller\AccountsReceivableInvoiceAttachmentController;
use App\WebService\Billing\Controller\AccountsReceivableInvoiceController;

/**
 * The accounts receivable invoice.
 *
 * @ApiResource(iri="AccountsReceivableInvoice", attributes={
 *     "normalization_context"={"groups"={"accounts_receivable_invoice_read", "accounts_receivable_invoice_attachment_read"}},
 * },
 * collectionOperations={},
 * itemOperations={
 *     "get"={
 *         "method"="GET",
 *         "path"="/accounts_receivable_invoices/{id}.{_format}",
 *         "controller"=AccountsReceivableInvoiceController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"accounts_receivable_invoice_read"}}
 *     },
 *     "get_attachment"={
 *         "method"="GET",
 *         "path"="/accounts_receivable_invoices/{id}/attachment.{_format}",
 *         "controller"=AccountsReceivableInvoiceAttachmentController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"accounts_receivable_invoice_attachment_read"}}
 *     },
 * })
 */
class AccountsReceivableInvoice
{
    /**
     * @ApiProperty(identifier=true)
     */
    private $id;

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
     * ARInvoice constructor.
     *
     * @param string      $invoiceNumber
     * @param string      $filename
     * @param string      $contentType
     * @param int         $filesize
     * @param string|null $url
     */
    public function __construct(string $invoiceNumber, ?string $filename, ?string $contentType, ?int $filesize, ?string $url = null)
    {
        $this->id = $invoiceNumber;
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
