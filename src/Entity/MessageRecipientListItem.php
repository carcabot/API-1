<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * An message recipient source list item.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"message_recipient_list_item_read"}},
 *     "denormalization_context"={"groups"={"message_recipient_list_item_write"}},
 * })
 */
class MessageRecipientListItem extends ListItem
{
    /**
     * @var CustomerAccount
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ApiProperty()
     */
    protected $customer;

    /**
     * @var Message
     *
     * @ORM\OneToOne(targetEntity="Message", inversedBy="recipient", cascade={"persist"})
     * @ApiProperty()
     */
    protected $message;

    /**
     * @var string[]
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty()
     */
    private $messageAddresses;

    /**
     * @return CustomerAccount
     */
    public function getCustomer(): CustomerAccount
    {
        return $this->customer;
    }

    /**
     * @param CustomerAccount $customer
     */
    public function setCustomer(CustomerAccount $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @param Message $message
     */
    public function setMessage(Message $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string[]
     */
    public function getMessageAddress(): array
    {
        return $this->messageAddresses;
    }

    /**
     * @param string[] $messageAddresses
     */
    public function setMessageAddress(array $messageAddresses): void
    {
        $this->messageAddresses = $messageAddresses;
    }
}
