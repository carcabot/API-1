<?php

declare(strict_types=1);

namespace App\Domain\Command\MessageTemplate;

use App\Entity\MessageTemplate;

/**
 * Updates message number.
 */
class UpdateMessageNumber
{
    /**
     * @var MessageTemplate
     */
    private $message;

    /**
     * @param MessageTemplate $message
     */
    public function __construct(MessageTemplate $message)
    {
        $this->message = $message;
    }

    /**
     * Gets the message.
     *
     * @return MessageTemplate
     */
    public function getMessage(): MessageTemplate
    {
        return $this->message;
    }
}
