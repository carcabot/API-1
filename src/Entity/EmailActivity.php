<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\EmailType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An email activity carried out on an item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"email_activity_read"}},
 *     "denormalization_context"={"groups"={"email_activity_write"}},
 * })
 */
class EmailActivity extends Activity
{
    /**
     * @var string|null The subject matter of the content.
     *
     * @ORM\Column(type="string", length=254, nullable=true)
     * @ApiProperty(iri="http://schema.org/about")
     */
    protected $about;

    /**
     * @var string[] A sub property of recipient. The recipient blind copied on a message.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty(iri="http://schema.org/bccRecipient")
     */
    protected $bccRecipients;

    /**
     * @var string[] A sub property of recipient. The recipient copied on a message.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty(iri="http://schema.org/ccRecipient")
     */
    protected $ccRecipients;

    /**
     * @var Collection<DigitalDocument> A CreativeWork attached to the message.
     *
     * @ORM\ManyToMany(targetEntity="DigitalDocument", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="email_activity_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="file_id", onDelete="CASCADE")},
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty(iri="http://schema.org/messageAttachment")
     * @ApiSubresource()
     */
    protected $messageAttachments;

    /**
     * @var string|null The email template string.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $template;

    /**
     * @var string[] A sub property of recipient. The recipient who was directly sent the message.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty(iri="http://schema.org/toRecipient")
     */
    protected $toRecipients;

    /**
     * @var EmailType|null The email type.
     *
     * @ORM\Column(type="email_type_enum", nullable=true)
     * @ApiProperty()
     */
    protected $type;

    public function __construct()
    {
        $this->bccRecipients = [];
        $this->ccRecipients = [];
        $this->messageAttachments = new ArrayCollection();
        $this->toRecipients = [];
    }

    /**
     * Sets about.
     *
     * @param string|null $about
     *
     * @return $this
     */
    public function setAbout(?string $about)
    {
        $this->about = $about;
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
     * Adds bccRecipient.
     *
     * @param string $bccRecipient
     *
     * @return $this
     */
    public function addBccRecipient(string $bccRecipient)
    {
        $this->bccRecipients[] = $bccRecipient;

        return $this;
    }

    /**
     * Removes bccRecipient.
     *
     * @param string $bccRecipient
     *
     * @return $this
     */
    public function removeBccRecipient(string $bccRecipient)
    {
        if (false !== ($key = \array_search($bccRecipient, $this->bccRecipients, true))) {
            \array_splice($this->bccRecipients, $key, 1);
        }

        return $this;
    }

    /**
     * Gets bccRecipients.
     *
     * @return string[]
     */
    public function getBccRecipients(): array
    {
        return $this->bccRecipients;
    }

    /**
     * Adds ccRecipient.
     *
     * @param string $ccRecipient
     *
     * @return $this
     */
    public function addCcRecipient(string $ccRecipient)
    {
        $this->ccRecipients[] = $ccRecipient;

        return $this;
    }

    /**
     * Removes ccRecipient.
     *
     * @param string $ccRecipient
     *
     * @return $this
     */
    public function removeCcRecipient(string $ccRecipient)
    {
        if (false !== ($key = \array_search($ccRecipient, $this->ccRecipients, true))) {
            \array_splice($this->ccRecipients, $key, 1);
        }

        return $this;
    }

    /**
     * Gets ccRecipients.
     *
     * @return string[]
     */
    public function getCcRecipients(): array
    {
        return $this->ccRecipients;
    }

    /**
     * Adds messageAttachment.
     *
     * @param DigitalDocument $messageAttachment
     *
     * @return $this
     */
    public function addMessageAttachment(DigitalDocument $messageAttachment)
    {
        $this->messageAttachments[] = $messageAttachment;

        return $this;
    }

    /**
     * Removes messageAttachment.
     *
     * @param DigitalDocument $messageAttachment
     *
     * @return $this
     */
    public function removeMessageAttachment(DigitalDocument $messageAttachment)
    {
        $this->messageAttachments->removeElement($messageAttachment);

        return $this;
    }

    /**
     * Gets messageAttachments.
     *
     * @return DigitalDocument[]
     */
    public function getMessageAttachments(): array
    {
        return $this->messageAttachments->getValues();
    }

    /**
     * Sets template.
     *
     * @param string|null $template
     *
     * @return $this
     */
    public function setTemplate(?string $template)
    {
        $this->template = $template;
    }

    /**
     * Gets template.
     *
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * Adds toRecipient.
     *
     * @param string $toRecipient
     *
     * @return $this
     */
    public function addToRecipient(string $toRecipient)
    {
        $this->toRecipients[] = $toRecipient;

        return $this;
    }

    /**
     * Removes toRecipient.
     *
     * @param string $toRecipient
     *
     * @return $this
     */
    public function removeToRecipient(string $toRecipient)
    {
        if (false !== ($key = \array_search($toRecipient, $this->toRecipients, true))) {
            \array_splice($this->toRecipients, $key, 1);
        }

        return $this;
    }

    /**
     * Gets toRecipients.
     *
     * @return string[]
     */
    public function getToRecipients(): array
    {
        return $this->toRecipients;
    }

    /**
     * Gets email type.
     *
     * @return EmailType|null
     */
    public function getType(): ?EmailType
    {
        return $this->type;
    }

    /**
     * Sets email type.
     *
     * @param EmailType|null $type
     *
     * @return $this
     */
    public function setType(?EmailType $type)
    {
        $this->type = $type;

        return $this;
    }
}
