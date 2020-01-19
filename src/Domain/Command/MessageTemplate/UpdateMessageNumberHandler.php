<?php

declare(strict_types=1);

namespace App\Domain\Command\MessageTemplate;

use App\Model\MessageTemplateNumberGenerator;

class UpdateMessageNumberHandler
{
    /**
     * @var MessageTemplateNumberGenerator
     */
    private $messageNumberGenerator;

    /**
     * @param MessageTemplateNumberGenerator $messageNumberGenerator
     */
    public function __construct(MessageTemplateNumberGenerator $messageNumberGenerator)
    {
        $this->messageNumberGenerator = $messageNumberGenerator;
    }

    public function handle(UpdateMessageNumber $command): void
    {
        $message = $command->getMessage();
        $messageNumber = $this->messageNumberGenerator->generate($message);

        $message->setMessageNumber($messageNumber);
    }
}
