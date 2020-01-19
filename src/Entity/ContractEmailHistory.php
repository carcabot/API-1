<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\WebService\Billing\Controller\ContractEmailHistoriesController;
use App\WebService\Billing\Controller\ContractEmailHistoryController;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The contract email message history.
 *
 * @ApiResource(iri="ContractEmailHistory", attributes={
 *     "normalization_context"={"groups"={"contract_email_history_read"}},
 * },
 * collectionOperations={
 *     "get",
 *     "get_email_histories"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/email_message_histories.{_format}",
 *         "controller"=ContractEmailHistoriesController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"contract_email_history_read"}},
 *     },
 * },
 * itemOperations={
 *     "get",
 *     "get_email_history"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/email_message_histories/{emailHistoryId}.{_format}",
 *         "controller"=ContractEmailHistoryController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"contract_email_history_read"}},
 *     },
 * })
 */
class ContractEmailHistory
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var string|null The subject matter of the content.
     */
    protected $about;

    /**
     * @var Collection<ContractEmailHistoryAttachment> The email history attachment.
     */
    protected $attachments;

    /**
     * @var string|null A sub property of recipient. The recipient who was directly sent the message.
     */
    protected $toRecipients;

    public function __construct($id, ?string $about, ?string $toRecipients)
    {
        $this->id = $id;
        $this->about = $about;
        $this->toRecipients = $toRecipients;
        $this->attachments = new ArrayCollection();
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
     * Gets about.
     *
     * @return string|null
     */
    public function getAbout(): ?string
    {
        return $this->about;
    }

    /**
     * Adds attachment.
     *
     * @param ContractEmailHistoryAttachment $attachment
     *
     * @return $this
     */
    public function addConsumptionHistory(ContractEmailHistoryAttachment $attachment)
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * Gets attachments.
     *
     * @return ContractEmailHistoryAttachment[]
     */
    public function getAttachments(): array
    {
        return $this->attachments->getValues();
    }

    /**
     * Gets toRecipients.
     *
     * @return string|null
     */
    public function getToRecipients(): ?string
    {
        return $this->toRecipients;
    }
}
