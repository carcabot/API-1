<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: mufeer
 * Date: 2/1/19
 * Time: 7:05 PM.
 */

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class ToDesign
{
    /**
     * @var string|null The status
     *
     * @ODM\Field(type="string", name="status")
     */
    protected $status;

    /**
     * @var string|null The detail message
     *
     * @ODM\Field(type="string", name="detail_message")
     */
    protected $detailMessage;

    /**
     * @var string|null The summary message
     *
     * @ODM\Field(type="string", name="summary_message")
     */
    protected $summaryMessage;

    /**
     * @var string|null The image url
     *
     * @ODM\Field(type="string", name="image_url")
     */
    protected $imageUrl;

    /**
     * @var int|null The sequence number
     *
     * @ODM\Field(type="int", name="sequence_number")
     */
    protected $sequenceNumber;

    /**
     * @var string|null The return url
     *
     * @ODM\Field(type="string", name="return_url")
     */
    protected $returnUrl;

    /**
     * Gets status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Gets detailMessage.
     *
     * @return string|null
     */
    public function getDetailMessage(): ?string
    {
        return $this->detailMessage;
    }

    /**
     * Gets summaryMessage.
     *
     * @return string|null
     */
    public function getSummaryMessage(): ?string
    {
        return $this->summaryMessage;
    }

    /**
     * Gets imageUrl.
     *
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * Gets sequenceNumber.
     *
     * @return int|null
     */
    public function getSequenceNumber(): ?int
    {
        return $this->sequenceNumber;
    }

    /**
     * Gets returnUrl.
     *
     * @return string|null
     */
    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }
}
